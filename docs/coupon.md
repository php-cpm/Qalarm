# 优惠券系统接口

## 1 系统介绍

优惠券（不包含顺风车业务下的优惠券）

## 2 环境说明

服务地址使用QConf管理，[使用参见QConf](http://wiki.corp.ttyongche.com:8360/confluence/display/tech/qconf)，服务地址可以通过QConf获得（***推荐***），也可以只连ip地址。

### 2.1 测试环境

* UCloud测试环境: 10.6.12.178:12810
* Qconf1: /qconf_root/coupon/providers_test

* Local测试环境: 172.16.10.10:12810
* Qconf2: /qconf_root/coupon/providers_test2

### 2.2 线上环境

* addr: 10.10.135.97:12810
* addr: 10.6.4.111:12810
* Qconf: /qconf_root/coupon/providers

## 3 接口基础定义

### 3.1 请求格式

- 字符编码统一使用 `UTF-8`
- 如无特殊说明，均使用 `POST` 方法进行请求
- 请求数据中使用json编码，需在Header中添加 `Content-Type: application/json`，并将编码后的数据放请求 body 中，服务端接受后按照对应编码对数据解码

### 3.2 响应格式

- 返回数据编码统一使用 `UTF-8`
- JSON 基本格式为：

        {
            "errno":0,
            "errmsg":"",
            "data":{}

            /* errno：int 0表示成功，非0表示失败，具体失败代码请参考接口定义；*/
            /* errmsg：string 接口失败时给用户的错误信息； */
            /* data：{} 所有API返回数据都放在data中。 */
        }

### 3.3 Pagination

需要分页的接口将使用此定义进行分页的处理。
该定义支持两种分页模式，两种模式的实现情况由具体接口决定，并会在接口中详细说明。

#### 3.3.1 页码模式

**请求参数**

- `page_index`: 页码，从1开始
- `page_size`: 每页的元素数量

**请求示例**

    GET /module/action?page_index=1&page_size=15

或者

    POST /module/action
    {
        "page_index": 1,
        "page_size": 15,
    }

**响应示例**

    {
        "page": {
            "size":     15, // 每页请求的大小
            "count":    12, // 每页实际的大小
            "total":    41, // 总大小
            "has_more": 1,  // 是否有更多
            "index":    1,  // 当前页码
        },

        // 列表数据...
    }

## 4 接口列表

### 4.1 /api/v1/awards 获取奖品列表

**请求参数**

- `buss_type`: 业务类型
    - `0`: 所有
    - `1`: 顺风车-必应
    - `2`: 顺风车-其他
    - `10`: 车生活-洗车
    - `11`: 车生活-保养
    - `12`: 车生活-贴膜
    - `13`: 车生活-漆面护理
    - `14`: 车生活-内饰深度清洁
    - `15`: 车生活-钣喷
    - `16`: 车生活-年检
    - `17`: 车生活-进京证
    - `18`: 车生活-违章
- `coupon_type`: 优惠券类型
    - `10`: 优惠券  代金券   优惠金额{argv1}
    - `11`: 优惠券  抵用券   支付金额{argv1}  抵用金额{argv2}元
    - `12`: 优惠券  通乘券   通乘金额{argv1}
    - `13`: 优惠券  折扣券   全单折扣{argv1}折
    - `21`: 现金券           金额{argv1}元
    - `31`: 实物券           物品名称{argv1}
    - `41`: 助补             补助{argv1}倍
    - `51`: 红包             金额{argv1}至{argv2}元
- `city_id`: 城市id

**返回格式**

    {
        "page":   struct Pagination
        "results": [
            {
                "id":                    1,                               // 奖品id
                "coupon_type":           61,                              //优惠卷的类型
                "buss_type":      13,                              //优惠卷的业务类型
                "coupon_name":           "注册送20代金券",                // 奖品名称
                "coupon_type_name":      "优惠券-代金券",                 // 优惠券类型名
                "buss_type_name":        "车服务-洗车",                 // 业务类型名
                "coupon_bind_code":"     "123121",                        // 绑定码
                "argv1":                 10,                              // 根据优惠券类型的参数1
                "argv2":                 0,                               // 根据优惠券类型的参数2
                "status":                1,                               // * 1 未使用 2 占用 3 使用完成 4 过期失效*/
                "valid_begin":           1397491200000,                   // 开始时间
                "valid_end":             1397836800000,                   // 失效时间
            },
            { /* */ }
        ]
    }

### 4.2 /api/v1/coupons 获取优惠券列表

**请求参数**
- `buss_type` 业务类型
    - `0`: 所有
    - `1`: 顺风车-必应
    - `2`: 顺风车-其他
    - `10`: 车生活-洗车
    - `11`: 车生活-保养
    - `12`: 车生活-贴膜
    - `13`: 车生活-漆面护理
    - `14`: 车生活-内饰深度清洁
    - `15`: 车生活-钣喷
    - `16`: 车生活-年检
    - `17`: 车生活-进京证
    - `18`: 车生活-违章
- `user_id`   用户id
- `status`    优惠券状态 /*0 所有 1 2 3 4 如下定义*/

**可选参数**
- `order_id`  订单id

**返回格式**
```c
{
    "page":   struct Pagination
    "results": [
        {
            "id":             1,                               // 优惠券id
            "userid":         2,                               // 用户id
            "buss_type":      1,                               // 业务id号
            "coupon_type":    2,                               // 优惠券类型
            "argv1":          10,                              // 根据优惠券类型的参数1
            "argv2":          0,                               // 根据优惠券类型的参数2
            "coupon_name":    "10元代金券",                    // 代金券名
            "send_reason":    "注册发放",                      // 发放原因
            "status":         1,                               // * 1 未使用 2 占用 3 使用完成 4 过期失效*/
            "valid_begin":    1397491200000,                   // 开始时间
            "valid_end":      1397836800000,                   // 失效时间
        },
        { /* */ }
    ]
}
```

**coupon_type说明**
- `10`: 优惠券  代金券   优惠金额{argv1}
- `11`: 优惠券  抵用券   支付金额{argv1}  抵用金额{argv2}元
- `12`: 优惠券  通乘券   通乘金额{argv1}
- `13`: 优惠券  折扣券   全单折扣{argv1}折
- `21`: 现金券           金额{argv1}元
- `31`: 实物券           物品名称{argv1}
- `41`: 助补             补助{argv1}倍
- `51`: 红包             金额{argv1}至{argv2}元


### 4.3 /api/v1/coupons/detail 获取优惠券详情
**请求参数**
- `coupon_id `     优惠券id

**返回格式**
```c
{
     "id":             1,                               // 优惠券id
     "userid":         2,                               // 用户id
     "buss_type":      1,                               // 业务id号
     "coupon_type":    2,                               // 优惠券类型
     "argv1":          10,                              // 根据优惠券类型的参数1
     "argv2":          0,                               // 根据优惠券类型的参数2
     "coupon_name":    "10元代金券",                    // 代金券名
     "send_reason":    "注册发放",                      // 发放原因
     "status":         1,                               // * -1 未兑换 1 未使用 2 占用 3 使用完成 4 过期失效*/
     "valid_begin":    1397491200000,                   // 开始时间
     "valid_end":      1397836800000,                   // 失效时间
}
```

### 4.4 /api/v1/coupons/exchange  优惠码兑换优化券
**请求参数**
- `coupon_code` 优惠码
- `user_id`     用户id

**返回格式**
```c
{
    "coupon_id":      12,                              // 兑换的优惠券id
}
```

### 4.5 /api/v1/coupons/operation  操作优惠券
**请求参数**
- `coupon_id`   优惠券id
- `user_id`     用户id
- `action`      use|done|reback

**返回格式**
```c
{
}
```

### 4.6 /api/v1/coupons/dispense 发放优惠券
**请求参数**
- `user_id`     用户id
- `mobile`      用户手机号
- `money`       优惠金额
- `buss_type`   业务类型
- `start_date`  有效期开始时间 timestamp
- `end_date`    有效期结束时间 timestamp
- `reason_id`   发放原因类型 /* 1 用户补偿 2 内部测试 3 车服务-保养*/
- `reason` 发放原因
- `notice_sms` 是否发送短信 /* 1是发送 其他为不发送*/

**返回格式**
```c
{
}
```

### 4.7 /api/v1/actives  活动列表
**请求参数**
- `active_type`     活动类型
- `coupon_type`     优惠券类型
    - 10 优惠券  代金券   优惠金额{argv1}
    - 11 优惠券  抵用券   支付金额{argv1}  抵用金额{argv2}元
    - 12 优惠券  通乘券   通乘金额{argv1}
    - 13 优惠券  折扣券   全单折扣{argv1}折
    - 21 现金券           金额{argv1}元
    - 31 实物券           物品名称{argv1}
    - 41 助补             补助{argv1}倍
    - 51 红包             金额{argv1}至{argv2}元
- `buss_type`       业务类型
    - 0: 所有
    - 1: 顺风车-必应
    - 2: 顺风车-其他
    - 10: 车生活-洗车
    - 11: 车生活-保养
    - 12: 车生活-贴膜
    - 13: 车生活-漆面护理
    - 14: 车生活-内饰深度清洁
    - 15: 车生活-钣喷
    - 16: 车生活-年检
    - 17: 车生活-进京证
    - 18: 车生活-违章
- `notice_type`     推送方式
- `cityid`          城市
- `start_time`      开始时间
- `end_time`        结束时间   /*空表示长期活动*/

**返回格式**
```c
{
    "page":  struct Pagination,
    "results": [
        {
            "active_name":       "七夕情",
            "active_code":       "ccadadfa",
            "active_alise":      "七夕",
            "shared":            0,
            "coupon":            {
                "coupon_type":    "优惠券/代金券",
                "buss_type":      10,
                "argv1":          30,
                "argv2":          0
            }
            "coupon_quota":      1000,
            "coupon_take_count": 500,
            "coupon_use_count":  200,
            "coupon_use_way":    1,
            "cityids":           "1,2",
            "active_url":        "http://demo.com",
            "status":            3,
            "start_time":        "2015-09-25",
            "end_time":          "2015-11-12"
        },
        { /*...*/ }
    ]
}
```

### 4.8 /api/v1/actives/creation
**请求参数**
- `active_name`       活动名
- `coupon_bind_code`  活动绑定码
- `shared`            是否专享
- `coupon_use_way`    使用方式
- `coupon_quota`      配额领取次数
- `cityids`           城市
- `start_time`        开始时间
- `end_time`          结束时间   /* 空表示长期活动 */

- `buss_type`         业务类型

**可选参数**
- `active_alise`     活动别名

**返回格式**
```c
{
    "active_id": 1
}
```

### 4.9 /api/v1/notices
**请求参数**
- ``
**可选参数**

**返回格式**
```c
```

### 4.10 /api/v1/notices
**请求参数**
- ``
**可选参数**

**返回格式**
```c
```

### 4.11 /api/v1/notice/creation

**请求参数**

- `active_id`         活动id
- `notice_mobiles`    推送手机号

- `notice_time`       推送时间
- `notice_way`        推送方式 1 sms 2 push
- `notice_content`    推送内容
- `admin_user_id`     运营人员id
- `admin_user_name`   运营任务名字

**可选参数**
- `notice_link`       推送链接

**返回格式**

    {
        "notice_id":   12
    }