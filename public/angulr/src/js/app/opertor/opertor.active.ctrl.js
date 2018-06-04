app.controller("ActiveCtrl", ['NgTableParams', function (NgTableParams) {
    var data = [{name: "Moroni", age: 50}, {name: "Mor", age: 51} ];
    this.tableParams = new NgTableParams({
        page: 1,
        count: 1
    }, {
        total: data.length,
        data : data
    });
}]);



