<?php

namespace App\Http\Controllers;


use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

use Carbon\Carbon;

use App\Components\Utils\Paginator; 
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\Constants;

use App\Models\Gaea\OpContent;
use App\Models\Gaea\OpContentType;

class ContentController extends Controller
{

    /**
     * @brief fetchContentTypes 支持三级类型
     * @Return 树形结构
     */
    public function fetchContentTypesTree(Request $request)
    {
        $rows = $this->getAllContentTypes();
       
        // 三级的树形结构
        $tree['tree'] = $this->searchSubIds(0, $rows);

        foreach ($rows as $row) {
            $tree['types'][$row->id] = array('name'=>$row->name, 'icon'=>$row->icon, 'url'=>$row->url);
        }

        return response()->clientSuccess($tree);
    }
    
    /**
     * @brief fetchContentTypes 支持三级类型
     * @Return  
     */
    public function fetchContentTypes(Request $request)
    {
        $contentType = $request->input('content_type');
        if (!empty($contentType)) {
            $rows = $this->getAllContentTypes();
            $idsTree = $this->searchSubIds($contentType, $rows);

            $ids = $this->recurenceArrayToArray($idsTree);

            $query = OpContentType::whereIn('id', $ids)->orderBy('created_at', 'desc');
        } else {
            $query = OpContentType::orderBy('created_at', 'desc');
        }
        $paginator = new Paginator($request);
        $contents  = $paginator->runQuery($query);

        return $this->responseList($paginator, $contents);
    }

    // 选择当前id的所有子id
    private function searchSubIds($id, $rows)
    {
        $ids = array();
        foreach ($rows as $row)
        {
            if ($row->parent_id == $id) {
                $ids[$row->id] = $row->id;
            }
        }

        if (count($ids) == 0) return [];

        foreach ($ids as $id => $value) {
            $ids[$id] = $this->searchSubIds($id, $rows);
        }

        return $ids;
    }

    private function getAllContentTypes()
    {
        $rows = DB::connection('gaea')->select('select 
        a.id as id, a.parent_id as parent_id, a.name as name, a.icon as icon, a.url as url
        from
        (
            op_content_type as a left join op_content_type as b on a.id=b.parent_id
        ) 
        left join op_content_type as c on b.id=c.parent_id order by a.parent_id asc');

        return $rows;
    }

    public function updateContentTypes(Request $request)
    {
        $this->validate($request, [
            'parent_id'  => 'required',
            'name'       => 'required'
        ]);

        $parent =  OpContentType::where('id', $request->input('parent_id'));
        if ($parent == null) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, "父类型不存在");
        }

        $name = $request->input('name'); 
        $parentId = $request->input('parent_id');
        $type = OpContentType::where('name', $name)->where('parent_id', $parentId)->first();
        if ($type == null) $type = new OpContentType;
        {
            $type->name         = $name;
            $type->parent_id    = $parentId;
            $type->icon         = $request->input('icon');
            $type->url          = $request->input('url');
        }
        $type->save();

        return response()->clientSuccess(['id' => $type->id]);
    }

    /**
     * @brief 得到父类型信息
     * @param $currentType
     * @return 
     */
    public function getParentContentType($currentType)
    {
        $ret = [
            'a_id' => '0',
            'b_id' => '0',
            'c_id' => '0',
            'name' => ''
        ];

        $rows = DB::connection('gaea')->select('select 
        a.id as a_id, b.id as b_id, a.name as a_name, b.name as b_name
        from op_content_type a inner join op_content_type b  on a.parent_id=b.id and a.id='.$currentType);

        // 此为一级类型
        if ($rows == null) {
            $row = OpContentType::where('id', $currentType)->first();
            $ret['a_id'] = (string)$row->id;
            $ret['name'] = $row->name;
            return $ret;
        }

        $row = $rows[0];

        $currentType = $row->b_id;
        $rows = DB::connection('gaea')->select('select 
        a.id as a_id, b.id as b_id, a.name as a_name, b.name as b_name
        from op_content_type a inner join op_content_type b  on a.parent_id=b.id and a.id='.$currentType);

        // 为二级类型
        if ($rows == null) {
            $ret['a_id'] = (string)$row->b_id;
            $ret['b_id'] = (string)$row->a_id;
            $ret['name'] = $row->b_name.'/'.$row->a_name;
            return $ret;
        }


        $row1 = $rows[0];
        // 三级类型
        $ret['a_id'] = (string)$row1->b_id;
        $ret['b_id'] = (string)$row1->a_id;
        $ret['c_id'] = (string)$row->a_id;
        $ret['name'] = $row1->b_name.'/'.$row1->a_name.'/'.$row->a_name;
        return $ret;
    }

    private function recurenceArrayToArray($input)
    {
        $ids = array();

        if (is_array($input)) {
            $ids = array_keys($input);
            foreach ($input as $one) {
                $ids = array_merge($ids, $this->recurenceArrayToArray($one));
            }
            return $ids;
        } else {
            return array();
        }
    }

    /**
     * @brief 获取文章内容，如果contentType有子类型，则获取包括子类型的文章
     * @param $request
     * @return 
     */
    public function fetchContents(Request $request)
    {
        $contentType = $request->input('content_type', 0);

        if (!empty($contentType)) {
            $rows = $this->getAllContentTypes();
            $idsTree = $this->searchSubIds($contentType, $rows);

            $ids = $this->recurenceArrayToArray($idsTree);

            // 包括自己
            $ids = array_merge([(int)$contentType], $ids);

            $query = OpContent::whereIn('type_id', $ids)->orderBy('published_at', 'desc');   
        } else {
            $query = OpContent::orderBy('published_at', 'desc');
        }

        $isTop = $request->input('is_top', -1);   // -1 表示全部
        if ($isTop != '-1') {
            $query->where('is_top', $isTop);
        }

        $paginator = new Paginator($request);
        $contents  = $paginator->runQuery($query);

        return $this->responseList($paginator, $contents);
    }

    public function fetchContent(Request $request)
    {
        $this->validate($request, [
            'content_id' => 'required'
        ]);

        $content = OpContent::where('id', $request->input('content_id'))->first();
        $parentType = $this->getParentContentType($content->type_id);

        $data = [
            'title'          => $content->title,        
            'content'        => $content->content,        
            'summary'        => $content->summary,        
            'published_at'   => $content->published_at,
            'keywords'       => $content->keywords,
            'type_id'        => $content->type_id,
            'parent_type'    => $parentType,
        ];

        return response()->clientSuccess($data);
    }

    public function updateContent(Request $request)
    {
        $this->validate($request, [
            'content_type' => 'required',
            'title'        => 'required',
            'content'      => 'required',
            'published_at' => 'required',
        ]);

        $contentId = $request->input('content_id', 0);
        $content = null;
        if ($contentId != 0) {
            $content = OpContent::where('id', $contentId)->first();
        }
        
        if ($content == null) $content = new OpContent;
        {
            $content->type_id     = $request->input('content_type');
            $content->title       = $request->input('title');
            $content->summary     = $request->input('summary');
            $content->content     = $request->input('content');
            $content->author      = $request->input('author');
            $content->published_at= $request->input('published_at');
            $content->keywords    = $request->input('keywords');
        }
        $content->save();

        return response()->clientSuccess(['id' => $content->id]);
    }

    /**
     * @ 内容状态转移
     * @param $request
     * @return 
     */
    public function contentStatusTransfer(Request $request) 
    {
        $this->validate($request, [
            'action'      => 'required',
            'content_id'  => 'required',
        ]);
        
        $content = OpContent::where('id', $request->input('content_id'))->first();
        if ($content == null) {
            return response()->clientError(ErrorCodes::ERR_FAILURE, '没有此文章');
        }
        
        $action = $request->input('action');
        if ($action == 'publish') {
            $content->status = OpContent::CONTENT_PUBLISH;
        }

        if ($action == 'top') {
            $content->is_top = OpContent::CONTENT_TOP;
        }
        
        if ($action == 'untop') {
            $content->is_top = OpContent::CONTENT_NOT_TOP;
        }
        $content->save();
        
        return response()->clientSuccess(['id' => $content->id]);
    }



    /**
     * @brief responseList 组装数据
     * @Param $paginator
     * @Param $collection
     * @Return  
     */
    protected function responseList($paginator, $collection, $callee='export')
    {
        return response()->clientSuccess([
            'page'     => $paginator->info($collection),
            'results'  => $collection->map(function($item, $key) use ($callee) {
                return call_user_func([$item, $callee]);
            }),
        ]);
    }
}
