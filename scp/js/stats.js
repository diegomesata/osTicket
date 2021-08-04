var app = angular.module("myApp", []);

app.controller("myCtrl", function($scope) {
    
    $scope.data = [];
    
    $scope.deps = [];
    
    $scope.tasks = [];
    
    $scope.orgs = [];

    $scope.qHeader = [];
    
    $scope.quality = [];

    console.log("Hola mundo");
    
    function init() {
    
        var res = JSON.parse($("#jsonres").val());    
    
        $scope.data = formatData(res);
        calcTotal($scope.data, true);
        
        res = JSON.parse($("#deps").val());    
        
        $scope.deps = formatData(res);
        calcTotal($scope.deps, false);
        
        
        res = JSON.parse($("#tasks").val());
        $scope.tasks = formatData(res);
        calcTotal($scope.tasks, false);
        
        res = JSON.parse($("#orgs").val());
        $scope.orgs = formatData(res);
        calcTotal($scope.orgs, false);

        res = JSON.parse($("#quality").val());
        var items =  JSON.parse($("#qualityitems").val());

        items = _.sortBy(items, function(i){return i.quality_item_id});

        $scope.qHeader = items;
        $scope.quality = parseQuality(res, items);

        console.log($scope.quality, items);
    }

    function parseQuality(data, items){

        var res = [];
        
        _.each(data, function(d){
            
          var row = _.findWhere(res, {Staff: d.Staff});
          
          if(!row) {
              row = {Staff: d.Staff, items: []};
              _.each(items, function(i){
                row.items.push({id: i.quality_item_id, valor: ""});
              });                             
              res.push(row);
          }

          row.items[d.quality_item_id].valor = d.count;
            
        });

        return res;

    }
    
    function calcTotal(data, avgRespTime){
        
        var total = {Staff: "Total"};
        
        _.each(data, function(d){
            
            _.each(Object.keys(d), function(k){
               if(d[k] && !isNaN(parseFloat(d[k]))){
                   if(!total[k])
                      total[k]=0;
                      
                    total[k]+=d[k];
               }
            });
        });
        
        if(avgRespTime && data.length)
            total.RespTime = total.RespTime / data.length;
        else
            total.RespTime = null;
        
        data.push(total);
    }
    
    function formatData(res){
        
        var data = [];
        
        //Group by staff user
        _.each(res, function(d){
            
            _.each(Object.keys(d), function(k){
               if(d[k] && !isNaN(parseFloat(d[k])))
                d[k]=parseFloat(d[k]);
            });
          
          var row = _.findWhere(data, {Staff: d.Staff});
          
          if(!row) {
              row = {Staff: d.Staff, Assigned: 0, Open: 0, OpenOverdue: 0, Closed:0, ClosedOverdue: 0, Reopened: 0, RespTime:0};
              data.push(row);
          }
          
          //Creates property foreach state
          if(!row[d.STATUS])
            row[d.STATUS] = 0;
            
          row.Reopened += d.reopened;
            
          row[d.STATUS] += d.cnt;
          
          row.Assigned += d.cnt;
          
          if(d.STATUS == "Closed") {
              row.RespTime = d.resptime;
              row.ClosedOverdue = d.overdue;
          }
          else if (d.STATUS == "Open")
            row.OpenOverdue = d.overdue;
          
            
        });  
        
        return data;
    }
    
    init();
    
});