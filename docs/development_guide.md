# gaea开发规范 

## git 使用规范
- 开发功能或者bug fix需要创建分支；
- 提交时尽量写清楚此次提交的目的和作用；


## 命名规范
### laravel
- 路由命名规则  
api/{version}/module/action  命名都为小写，多个单词之间不用下划线连接。

- api 请求参数和响应  
命名都为小写，多个单词之间使用下划线连接。

- 文件命名  
驼峰法
- 变量命名  
驼峰法
花括号 类和方法另起一行
       方法内跟在函数后面，中间有空格

不用array，用[] 替代
查询多个条件之间需要换行
for foreach if while else 等后面用空格分开

### angular：
- 文件命名  
html、js文件统一按模块区分，子模块之间用.分隔, 如: account.auth.js; 一个js文件中只能有一个controller，controller名为文件名的驼峰形式；使用小写拼写; 为了方便管理api和前端model，把所有model集中放到一个文件中。

- 变量命名  
驼峰，如果需要直接展示接口返回值，则可以使用接口中的变量命名。


## 数据库设计规范
请参考：http://wiki.corp.ttyongche.com:8360/confluence/pages/viewpage.action?pageId=2097200


## 其他
- constants 中的变量只放全局共享的信息, 各个模块内聚。
- 需要对方法进行注释，特殊比较难懂的地方也需要添加注释；

