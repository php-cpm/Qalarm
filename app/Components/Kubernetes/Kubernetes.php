<?php
namespace App\Components\Kubernetes;

use App\Components\Utils\HttpUtil;
use App\Components\Utils\ErrorCodes;
use Log;
use Qconf;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use GuzzleHttp\Client as GuzzleClient;
use JMS\Serializer\SerializerBuilder;
use Kubernetes\Client\Adapter\Http\AuthenticationMiddleware;
use Kubernetes\Client\Adapter\Http\GuzzleHttpClient;
use Kubernetes\Client\Adapter\Http\HttpAdapter;
use Kubernetes\Client\Adapter\Http\HttpConnector;
use Kubernetes\Client\Client;
use Kubernetes\Client\Serializer\JmsSerializerAdapter;

use Kubernetes\Client\Model\ObjectMetadata;
use Kubernetes\Client\Model\KeyValueObject;
use Kubernetes\Client\Model\KeyValueObjectList;
use Kubernetes\Client\Model\Service;
use Kubernetes\Client\Model\ServicePort;
use Kubernetes\Client\Model\ServiceSpecification;
use Kubernetes\Client\Model\KubernetesNamespace;  
use Kubernetes\Client\Model\ReplicationControllerSpecification;
use Kubernetes\Client\Model\ReplicationController;
use Kubernetes\Client\Model\PodTemplateSpecification;
use Kubernetes\Client\Model\PodSpecification;
use Kubernetes\Client\Model\Container;
use Kubernetes\Client\Model\ContainerPort;
use Kubernetes\Client\Model\VolumeMount;
use Kubernetes\Client\Model\EnvironmentVariable;

use Kubernetes\Client\Exception\Exception;
use Kubernetes\Client\Exception\ClientError;
use Kubernetes\Client\Exception\ServerError;;
use Kubernetes\Client\Exception\ServiceNotFound;
use Kubernetes\Client\Exception\ReplicationControllerNotFound;

class Kubernetes
{
    const FROM = 'gaea_client';
    const GAEA_K8S_NAMESPACE  = 'gaea-ci';

    const ENV_TEST       = 1;
    const ENV_PRODUCTION = 2;

    const CD_ENV_NAME    = 'GAEA_CD_ENV';

    public static $configs = [
        self::ENV_TEST         => [
            'url'      => 'http://test.kubeapi.ttyongche.com',
        ],
        self::ENV_PRODUCTION   => [
            'url'      => 'http://10.9.62.156:8080',
        ]
    ];

    // pod status
    const POD_PHASE_PENDING    = 'Pending';
    const POD_PHASE_RUNNING    = 'Running';
    const POD_PHASE_SUCCEEDED  = 'Succeeded'; 
    const POD_PHASE_FAILED     = 'Failed';

    private static $env    = self::ENV_PRODUCTION;

    private static $client = null;

    //public static $subfixNameV1 = 'v1';
    //public static $subfixNameV2 = 'v2';

    // 设置k8s集群环境
    public static function setEnv($env = self::ENV_PRODUCTION)
    {/*{{{*/
        self::$env = $env;
    }/*}}}*/

    // 把projectName转换成k8s规定的命名规则:k8s对象名字只能是数字，小写字母和-的组合
    private static function parseProjectName($projectName) 
    {/*{{{*/
        $projectName = strtolower($projectName) ;
        $projectName = preg_replace('/[^a-z0-9]+/i','-',$projectName);

        return $projectName;
    }/*}}}*/

    public static function getClientInstance()
    {/*{{{*/
        $baseUrl  = self::$configs[self::$env]['url'];
        $k8sVersion  = 'v1';
        $username = null;
        $password = null;
        
        //取消单例模式;引发线上环境与测试环境冲突
        //if (static::$client == null) {
            $metaDataDir = __DIR__.'/../../../vendor/sroze/kubernetes-client/src/Resources/serializer';
            $serializer = SerializerBuilder::create()
                 ->addMetadataDir($metaDataDir, 'Kubernetes\Client')
                 ->build();
            $httpClient = new GuzzleHttpClient(new GuzzleClient([
                'defaults' => [
                'verify' => false,
                ],
                ]), $baseUrl, $k8sVersion);

            if ($username !== null) {
                $httpClient = new AuthenticationMiddleware($httpClient, $username, $password);
            }

            $connector = new HttpConnector(
                $httpClient,
                new JmsSerializerAdapter($serializer)
            );

            static::$client = new Client(
                new HttpAdapter(
                    $connector
                )
            );
        //}

        // 创建gaea-ci 的namespace
        static::createOrUpdateNamespace();

        return static::$client;
    }/*}}}*/

    private static function getNamespaceClient($client, $namespaceName = self::GAEA_K8S_NAMESPACE)
    {/*{{{*/
        $k8sNamespace = new KubernetesNamespace(new ObjectMetadata($namespaceName));
        return $client->getNamespaceClient($k8sNamespace);
    }/*}}}*/

    public static function createOrUpdateService($projectName, array $ports = [], $ciCdStepCurrentStep, $svcNameSubfix)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName);
        $ciCdStepCurrentStep = static::parseProjectName($ciCdStepCurrentStep);
        $servicePorts = [];
        foreach ($ports as $port) {
            $servicePorts[] = new ServicePort('name-'.$port, $port, 'TCP');
        }

        // 选择器为 POD的名称，不包括版本号
        $labels = new KeyValueObjectList();
        $labels->add(new KeyValueObject('name', static::getPodName($projectName, $ciCdStepCurrentStep)));

        $client = static::getClientInstance();

        // 使用clusterip模式, 只选择项目名，不精确到版本
        $specification = new ServiceSpecification(
            [
                'name'      => static::getPodName($projectName, $ciCdStepCurrentStep),
            ], 
            $servicePorts, 
            ServiceSpecification::TYPE_CLUSTER_IP,
            ServiceSpecification::SESSION_AFFINITY_CLIENT_IP
        );

        //$newSvc = new Service(new ObjectMetadata(static::getSvcName($projectName), $labels), $specification);
        $newSvc = new Service(new ObjectMetadata(static::getSvcName($projectName, $ciCdStepCurrentStep, $svcNameSubfix), $labels), $specification);
        $nameSpaceClient = static::getNamespaceClient($client);
        try {
            //$oldSvc = $nameSpaceClient->getServiceRepository()->findOneByName(static::getSvcName($projectName));
            $oldSvc = $nameSpaceClient->getServiceRepository()->findOneByName(static::getSvcName($projectName, $ciCdStepCurrentStep, $svcNameSubfix));
            $response = $nameSpaceClient->getServiceRepository()->update($newSvc);
            $clusterIp = $response->getSpecification()->getClusterIp();
            return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '', ['cluster_ip' => $clusterIp]);
        } catch (ServiceNotFound $e) {   // 不存在则创建
            try {
                $response = $nameSpaceClient->getServiceRepository()->create($newSvc);
                $clusterIp = $response->getSpecification()->getClusterIp();
                return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '', ['cluster_ip' => $clusterIp]);
            } catch (ClientError $e) {
                $errcode = $e->getStatus()->getCode();
                $errmsg  = $e->getStatus()->getMessage();
                return static::k8sReturn($errcode, $errmsg);
            }
        } catch (ClientError $e) {
            $errcode = $e->getStatus()->getCode();
            $errmsg  = static::k8sApiStatusErrorMsg($errcode);
            return static::k8sReturn($errcode, $errmsg);
        }
    }/*}}}*/

    public static function createOrUpdateService_bak($projectName, array $ports = [])
    {/*{{{*/
        $projectName = static::parseProjectName($projectName);
        $servicePorts = [];
        foreach ($ports as $port) {
            $servicePorts[] = new ServicePort('name-'.$port, $port, 'TCP');
        }

        // 选择器为 POD的名称，不包括版本号
        $labels = new KeyValueObjectList();
        $labels->add(new KeyValueObject('name', static::getPodName($projectName)));

        $client = static::getClientInstance();

        // 使用clusterip模式, 只选择项目名，不精确到版本
        $specification = new ServiceSpecification(
            [
                'name'      => static::getPodName($projectName),
            ], 
            $servicePorts, 
            ServiceSpecification::TYPE_CLUSTER_IP,
            ServiceSpecification::SESSION_AFFINITY_CLIENT_IP
        );

        $newSvc = new Service(new ObjectMetadata(static::getSvcName($projectName), $labels), $specification);
        $nameSpaceClient = static::getNamespaceClient($client);
        try {
            $oldSvc = $nameSpaceClient->getServiceRepository()->findOneByName(static::getSvcName($projectName));
            $response = $nameSpaceClient->getServiceRepository()->update($newSvc);
            $clusterIp = $response->getSpecification()->getClusterIp();
            return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '', ['cluster_ip' => $clusterIp]);
        } catch (ServiceNotFound $e) {   // 不存在则创建
            try {
                $response = $nameSpaceClient->getServiceRepository()->create($newSvc);
                $clusterIp = $response->getSpecification()->getClusterIp();
                return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '', ['cluster_ip' => $clusterIp]);
            } catch (ClientError $e) {
                $errcode = $e->getStatus()->getCode();
                $errmsg  = $e->getStatus()->getMessage();
                return static::k8sReturn($errcode, $errmsg);
            }
        } catch (ClientError $e) {
            $errcode = $e->getStatus()->getCode();
            $errmsg  = static::k8sApiStatusErrorMsg($errcode);
            return static::k8sReturn($errcode, $errmsg);
        }
    }/*}}}*/

    public static function deleteService($svcName)
    {/*{{{*/
        //$projectName = static::parseProjectName($projectName); 
        //$ciCdStepCurrentStep = static::parseProjectName($ciCdStepCurrentStep); 
        $client = static::getClientInstance();
        $nameSpaceClient = static::getNamespaceClient($client);

        try {
            //$oldSvc = $nameSpaceClient->getServiceRepository()->findOneByName(static::getSvcName($projectName, $ciCdStepCurrentStep));
            //$oldSvc = $nameSpaceClient->getServiceRepository()->findOneByName(static::getSvcName($projectName, $ciCdStepCurrentStep));
            $oldSvc = $nameSpaceClient->getServiceRepository()->findOneByName($svcName);
            $nameSpaceClient->getServiceRepository()->delete($oldSvc);
            return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '删除成功', []);
        } catch (ClientError $e) {
            $errcode = $e->getStatus()->getCode();
            $errmsg  = $e->getStatus()->getMessage();
            return static::k8sReturn($errcode, $errmsg);
        } catch (ServiceNotFound $e) {
            $errcode = ErrorCodes::ERR_FAILURE;
            $errmsg  = "不存在此svc";
            return static::k8sReturn($errcode, $errmsg);
        }
    }/*}}}*/

    public static function deleteService_bak($projectName)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $client = static::getClientInstance();
        $nameSpaceClient = static::getNamespaceClient($client);

        try {
            $oldSvc = $nameSpaceClient->getServiceRepository()->findOneByName(static::getSvcName($projectName));
            $nameSpaceClient->getServiceRepository()->delete($oldSvc);
            return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '删除成功', []);
        } catch (ClientError $e) {
            $errcode = $e->getStatus()->getCode();
            $errmsg  = $e->getStatus()->getMessage();
            return static::k8sReturn($errcode, $errmsg);
        } catch (ServiceNotFound $e) {
            $errcode = ErrorCodes::ERR_FAILURE;
            $errmsg  = "不存在此svc";
            return static::k8sReturn($errcode, $errmsg);
        }
    }/*}}}*/

    public static function updateSvcSelector($svcName, $svcSelector)
    {/*{{{*/
        $svcName = static::parseProjectName($svcName); 
        $client = static::getClientInstance();
        $nameSpaceClient = static::getNamespaceClient($client);

        try {

            $svc = $nameSpaceClient->getServiceRepository()->findOneByName($svcName);
            
            $labels = new KeyValueObjectList();
            $labels->add(new KeyValueObject('name', $svcSelector));

            // 使用clusterip模式, 只选择项目名，不精确到版本
            $specification = new ServiceSpecification(
                [
                    'name'      => $svcSelector,
                ], 
                $svc->getSpecification()->getPorts(),
                ServiceSpecification::TYPE_CLUSTER_IP,
                ServiceSpecification::SESSION_AFFINITY_CLIENT_IP
            );

            $newSvc = new Service(new ObjectMetadata($svcName, $labels), $specification);
            $response = $nameSpaceClient->getServiceRepository()->update($newSvc);
            return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '', []);

        }catch (ServiceNotFound $e) {
            $errcode = -1;
            $errmsg  = "没有此svc:$svcName";
            return static::k8sReturn($errcode, $errmsg);
        }
    }/*}}}*/

    public static function createOrUpdateRc($projectName, array $ports = [], array $command = [], array $volumeNames = [], $replica = 2, $version = 'v1.0', $imageName, $ciCdStepCurrentStep, $environmentVariables = [])
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $ciCdStepCurrentStep = static::parseProjectName($ciCdStepCurrentStep); 

        $containerPorts = [];
        $volumeMounts   = [];
        foreach ($ports as $port) {
            $containerPorts[] = new ContainerPort($projectName, $port, 'TCP');
        }

        foreach ($volumeNames as $volume) {
            $volumeMounts[] = new VolumeMount($projectName, $volume, true);
        }
        
        $labels = new KeyValueObjectList();
        $labels->add(new KeyValueObject('name', static::getPodName($projectName, $ciCdStepCurrentStep)));
        $labels->add(new KeyValueObject('version', $version));

        $environmentVariablesArray = [];
        foreach ($environmentVariables as $name => $value) {
            $environmentVariablesArray[] = new EnvironmentVariable('GAEA_'.$name, $value);
        }

        $rcs = new ReplicationControllerSpecification(
            $replica, 
            [
                 'name'      => static::getPodName($projectName, $ciCdStepCurrentStep),
                 'version'   => $version
            ], 
            new PodTemplateSpecification(
                new ObjectMetadata(static::getPodName($projectName, $ciCdStepCurrentStep), $labels),
                new PodSpecification(
                [
                    new Container(
                        static::getPodName($projectName, $ciCdStepCurrentStep),
                        static::getDockerImage($imageName, $version),
                        $environmentVariablesArray,
                        $containerPorts,
                        [],
                        Container::PULL_POLICY_IF_NOT_PRESENT,
                        $command
                    )
                    // FIXME
                 ])
            )
        );

        //dd($rcs);
        $client = static::getClientInstance();
        $nameSpaceClient = static::getNamespaceClient($client);
        //$newRc = new ReplicationController(new ObjectMetadata(static::getRcName($projectName, $version), $labels), $rcs);
        $rcName = static::getRcName($projectName, $version, $ciCdStepCurrentStep) ;
        $newRc = new ReplicationController(new ObjectMetadata($rcName, $labels), $rcs);

        try {
            //$oldRc = $nameSpaceClient->getReplicationControllerRepository()->findOneByName(static::getRcName($projectName, $version));
            $oldRc = $nameSpaceClient->getReplicationControllerRepository()->findOneByName(static::getRcName($projectName, $version, $ciCdStepCurrentStep ));
            $response = $nameSpaceClient->getReplicationControllerRepository()->update($newRc);
            return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '', []);
        } catch (ReplicationControllerNotFound $e) {   // 不存在则创建
            try {
                $response = $nameSpaceClient->getReplicationControllerRepository()->create($newRc);
                return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '', []);
            } catch (ClientError $e) {
                $errcode = $e->getStatus()->getCode();
                $errmsg  = $e->getStatus()->getMessage();
                return static::k8sReturn($errcode, $errmsg);
            }
        } catch (ClientError $e) {
            $errcode = $e->getStatus()->getCode();
            $errmsg  = $e->getStatus()->getMessage();
            return static::k8sReturn($errcode, $errmsg);
        }
    }/*}}}*/

    public static function deleteRc($projectName, $version, $ciCdStepCurrentStep )
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $ciCdStepCurrentStep = static::parseProjectName($ciCdStepCurrentStep); 
        $client = static::getClientInstance();
        $nameSpaceClient = static::getNamespaceClient($client);

        try {
            //$oldRc = $nameSpaceClient->getReplicationControllerRepository()->findOneByName(static::getRcName($projectName, $version));
            $k8sRcName = static::getRcName($projectName, $version, $ciCdStepCurrentStep );
            $oldRc = $nameSpaceClient->getReplicationControllerRepository()->findOneByName($k8sRcName);
            $nameSpaceClient->getReplicationControllerRepository()->delete($oldRc);
            return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '', []);
        } catch (ClientError $e) {
            $errcode = $e->getStatus()->getCode();
            $errmsg  = $e->getStatus()->getMessage();
            return static::k8sReturn($errcode, $errmsg);
        }
    }/*}}}*/

    public static function deleteRc_bak($projectName, $version)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $client = static::getClientInstance();
        $nameSpaceClient = static::getNamespaceClient($client);

        try {
            $oldRc = $nameSpaceClient->getReplicationControllerRepository()->findOneByName(static::getRcName($projectName, $version));
            $nameSpaceClient->getReplicationControllerRepository()->delete($oldRc);
            return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '', []);
        } catch (ClientError $e) {
            $errcode = $e->getStatus()->getCode();
            $errmsg  = $e->getStatus()->getMessage();
            return static::k8sReturn($errcode, $errmsg);
        }
    }/*}}}*/

    public static function getCurrentRc($projectName, $ciCdStepCurrentStep)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $ciCdStepCurrentStep = static::parseProjectName($ciCdStepCurrentStep); 
        $client = static::getClientInstance();
        $nameSpaceClient = static::getNamespaceClient($client);
        $rcsList = $nameSpaceClient->getReplicationControllerRepository()->findAll();

        $rcName = static::getRcName($projectName, '', $ciCdStepCurrentStep);

        $rcs = $rcsList->getReplicationControllers();
        // rc 名称命名规则为 rc-$projectName-版本号
        foreach ($rcs as $rc) {
            $currentRcName = $rc->getMetadata()->getName();
            $tmpList = explode('-', $currentRcName);
            $versionRand = array_pop($tmpList);
            $versionBuildId = array_pop($tmpList);
            //rc-test-a-level-574816e906c04-8850
            $version = $versionBuildId.'-'.$versionRand;

            // 去掉version版本号之后，比较rc的名字
            if ($rcName == join('-', $tmpList)) {
                return [$currentRcName, $version ];
            }
        }

        return [null, null ];
    }/*}}}*/

    public static function getCurrentRc_bak($projectName)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $client = static::getClientInstance();
        $nameSpaceClient = static::getNamespaceClient($client);
        $rcsList = $nameSpaceClient->getReplicationControllerRepository()->findAll();

        $rcName = static::getRcName($projectName, '');


        $rcs = $rcsList->getReplicationControllers();
        // rc 名称命名规则为 rc-$projectName-版本号
        foreach ($rcs as $rc) {
            $currentRcName = $rc->getMetadata()->getName();
            $tmpList = explode('-', $currentRcName);
            $version = array_pop($tmpList);

            // 去掉version版本号之后，比较rc的名字
            if ($rcName == join('-', $tmpList)) {
                return [$currentRcName, $version];
            }
        }

        return [null, null];
    }/*}}}*/

    public static function updateRcReplica($rcName, $replica)
    {/*{{{*/
        $rcName = static::parseProjectName($rcName); 
        $client = static::getClientInstance();
        $nameSpaceClient = static::getNamespaceClient($client);

        try {
            $rc = $nameSpaceClient->getReplicationControllerRepository()->findOneByName($rcName);

            // 更新replica数量
            $rc->getSpecification()->setReplicas($replica);
            $response = $nameSpaceClient->getReplicationControllerRepository()->update($rc);
            return static::k8sReturn(ErrorCodes::ERR_SUCCESS, '', []);
        }catch (ReplicationControllerNotFound $e) {
            $errcode = -1;
            $errmsg  = "没有此rc:$rcName";
            return static::k8sReturn($errcode, $errmsg);
        }
    }/*}}}*/

    public static function createOrUpdateNamespace($namespaceName = self::GAEA_K8S_NAMESPACE)
    {/*{{{*/
        $client = static::$client;
        $k8sNamespace = new KubernetesNamespace(new ObjectMetadata($namespaceName));

        // 如果不存在则创建
        if (!$client->getNamespaceRepository()->exists($namespaceName)) {
            $client->getNamespaceRepository()->create($k8sNamespace);
        }

        return $client->getNamespaceRepository()->exists($namespaceName);
    }/*}}}*/

    public static function getPodsByRc($projectName, $version, $ciCdStepCurrentStep )
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $ciCdStepCurrentStep = static::parseProjectName($ciCdStepCurrentStep); 

        $client = static::getClientInstance();
        $nameSpaceClient = static::getNamespaceClient($client);
        //$rc = $nameSpaceClient->getReplicationControllerRepository()->findOneByName(static::getRcName($projectName, $version));
        //
        
        $rcName = static::getRcName($projectName, $version, $ciCdStepCurrentStep);
        $rc = $nameSpaceClient->getReplicationControllerRepository()->findOneByName($rcName);
        $pods = $nameSpaceClient->getPodRepository()->findByReplicationController($rc);

        $pods = $pods->getPods();

        $ret = [];
        foreach ($pods as $pod) {
            // $containers = $pod->getState()->getContainerStatuses();
            // foreach ($containers as $container) {
            //     $ready = $container->isReady();
            //     $container->getState()->getWaiting();
            //     $container->getState()->getRunning();
            //     $container->getState()->getTerminated();
            // }
            $ret[] = [
                'phase'  => $pod->getStatus()->getPhase(),
                'hostip' => $pod->getStatus()->getHostIp(),
                'podip'  => $pod->getStatus()->getPodIp(),
                'desc'   => sprintf("启动状态：%s, 物理机IP：%s， 容器IP：%s\n", $pod->getStatus()->getPhase(), $pod->getStatus()->getHostIp(), $pod->getStatus()->getPodIp()),
            ];
        }

        return $ret;
    }/*}}}*/

    public static function getPodsByRc_bak($projectName, $version)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $client = static::getClientInstance();
        $nameSpaceClient = static::getNamespaceClient($client);
        $rc = $nameSpaceClient->getReplicationControllerRepository()->findOneByName(static::getRcName($projectName, $version));
        $pods = $nameSpaceClient->getPodRepository()->findByReplicationController($rc);

        $pods = $pods->getPods();

        $ret = [];
        foreach ($pods as $pod) {
            // $containers = $pod->getState()->getContainerStatuses();
            // foreach ($containers as $container) {
            //     $ready = $container->isReady();
            //     $container->getState()->getWaiting();
            //     $container->getState()->getRunning();
            //     $container->getState()->getTerminated();
            // }
            $ret[] = [
                'phase'  => $pod->getStatus()->getPhase(),
                'hostip' => $pod->getStatus()->getHostIp(),
                'podip'  => $pod->getStatus()->getPodIp(),
                'desc'   => sprintf("启动状态：%s, 物理机IP：%s， 容器IP：%s\n", $pod->getStatus()->getPhase(), $pod->getStatus()->getHostIp(), $pod->getStatus()->getPodIp()),
            ];
        }

        return $ret;
    }/*}}}*/

    private static function k8sReturn($errno, $errmsg = '', $data = [])
    {/*{{{*/
        return [
            'errno'  => $errno,
            'errmsg' => $errmsg,
            'data'   => $data
        ];
    }/*}}}*/

    private static function k8sApiStatusErrorMsg($code)
    {/*{{{*/
        $msg = '';
        switch ($code) {
        case '422':
            $msg = '请求部分数据非法，请检测';
            break;
        case '409':
            $msg = '您创建的资源已经存在';
            break;
        default:
            $msg = '未知错误';
            break;
        }

        return $msg;
    }/*}}}*/

    public static function getPodName($projectName, $ciCdStepCurrentStep)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $ciCdStepCurrentStep = static::parseProjectName($ciCdStepCurrentStep); 
        //return 'pod-'.$projectName;
        return 'pod-'.$projectName.'-'.$ciCdStepCurrentStep;
    }/*}}}*/

    public static function getPodName_bak($projectName)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        return 'pod-'.$projectName;
    }/*}}}*/

    // RC名称不固定，需要带上版本号
    public static function getRcName($projectName, $version, $ciCdStepCurrentStep)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $ciCdStepCurrentStep = static::parseProjectName($ciCdStepCurrentStep); 
        if (empty($version)) {
            //return 'rc-'.$projectName;
            return 'rc-'.$projectName.'-'.$ciCdStepCurrentStep;
        } else {
            //return 'rc-'.$projectName. '-'.$version;
            return 'rc-'.$projectName.'-'.$ciCdStepCurrentStep.'-'.$version;
        }
    }/*}}}*/

    // RC名称不固定，需要带上版本号
    public static function getRcName_bak($projectName, $version)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        if (empty($version)) {
            return 'rc-'.$projectName;
        } else {
            return 'rc-'.$projectName. '-'.$version;
        }
    }/*}}}*/

    public static function getSvcName($projectName, $ciCdStepCurrentStep, $svcNameSubfix)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $ciCdStepCurrentStep = static::parseProjectName($ciCdStepCurrentStep); 
        return 'svc-'.$projectName.'-'.$ciCdStepCurrentStep.'-'.$svcNameSubfix;
    }/*}}}*/

    public static function getPodSelectorName($projectName, $ciCdStepCurrentStep)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        $ciCdStepCurrentStep = static::parseProjectName($ciCdStepCurrentStep); 
        return 'pod-'.$projectName.'-'.$ciCdStepCurrentStep;
    }/*}}}*/

    public static function getSvcName_bak($projectName)
    {/*{{{*/
        $projectName = static::parseProjectName($projectName); 
        return 'svc-'.$projectName;
    }/*}}}*/

    public static function getDockerImage($imageName, $version)
    {/*{{{*/
        $registry = env('CI_KUBERNETES_DOCKER_REGISTRY');
        
        $tmpList = explode('-', $version);
        $version = array_pop($tmpList);
        $version = join('', $tmpList);
        return sprintf("%s/%s:%s", $registry, $imageName, $version);
    }/*}}}*/
}
