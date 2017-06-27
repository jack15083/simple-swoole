<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <script src="http://www.wechat.dev/js/ichart.1.2.1.min.js"></script>
    <title>Laravel</title>
    <script>
        $(function() {
            var data = [
                {
                    name: '杭州',
                    value: 35.75,
                    color: '#9d4a4a'
                },
                {
                    name: '嘉兴',
                    value: 29.84,
                    color: '#5d7f97'
                },
                {
                    name: '湖州',
                    value: 24.88,
                    color: '#97b3bc'
                },
                {
                    name: '绍兴',
                    value: 6.77,
                    color: '#a5aaaa'
                },
                {
                    name: '温州',
                    value: 2.02,
                    color: '#778088'
                },
                {
                    name: '富阳',
                    value: 0.73,
                    color: '#6f83a5'
                }];

            new iChart.Pie2D({
                render: 'canvasDiv',
                data: data,
                title: '2017年浙江观看时长排名前5的地区',
                legend: {
                    enable: true
                },
                showpercent: true,
                decimalsnum: 2,
                width: 800,
                height: 400,
                radius: 140
            }).draw();
        });

        $(function(){
            var data = [
                {
                    name : '北京',
                    value:[-9,1,12,20,26,30,32,29,22,12,0,-6],
                    color:'#1f7e92',
                    line_width:3
                }
            ];
            var chart = new iChart.LineBasic2D({
                render : 'canvasDiv2',
                data: data,
                title : '北京2012年平均温度情况',
                width : 800,
                height : 400,
                coordinate:{height:'90%',background_color:'#f6f9fa'},
                sub_option:{
                    hollow_inside:false,//设置一个点的亮色在外环的效果
                    point_size:16
                },
                labels:["一月","二月","三月","四月","五月","六月","七月","八月","九月","十月","十一月","十二月"]
            });
            chart.draw();
        });

        $(function(){
            var data = [
                {name : 'IE',value : 35.75,color:'#a5c2d5'},
                {name : 'Chrome',value : 29.84,color:'#cbab4f'},
                {name : 'Firefox',value : 24.88,color:'#76a871'},
                {name : 'Safari',value : 6.77,color:'#9f7961'},
                {name : 'Opera',value : 2.02,color:'#a56f8f'},
                {name : 'Other',value : 0.73,color:'#6f83a5'}
            ];

            new iChart.Bar2D({
                render : 'canvasDiv3',
                data: data,
                title : 'Top 5 Browsers from 1 to 29 Feb 2012',
                showpercent:true,
                decimalsnum:2,
                width : 800,
                height : 400,
                coordinate:{
                    scale:[{
                        position:'bottom',
                        start_scale:0,
                        end_scale:40,
                        scale_space:8,
                        listeners:{
                            parseText:function(t,x,y){
                                return {text:t+"%"}
                            }
                        }
                    }]
                }
            }).draw();
        });
        $(function(){
            var data = [
                {name : 'IE',value : 35.75,color:'#a5c2d5'},
                {name : 'Chrome',value : 29.84,color:'#cbab4f'},
                {name : 'Firefox',value : 24.88,color:'#76a871'},
                {name : 'Safari',value : 6.77,color:'#9f7961'},
                {name : 'Opera',value : 2.02,color:'#a56f8f'},
                {name : 'Other',value : 0.73,color:'#6f83a5'}
            ];

            new iChart.Column2D({
                render : 'canvasDiv4',
                data: data,
                title : 'Top 5 Browsers from 1 to 29 Feb 2012',
                showpercent:true,
                decimalsnum:2,
                width : 800,
                height : 400,
                coordinate:{
                    background_color:'#fefefe',
                    scale:[{
                        position:'left',
                        start_scale:0,
                        end_scale:40,
                        scale_space:8,
                        listeners:{
                            parseText:function(t,x,y){
                                return {text:t+"%"}
                            }
                        }
                    }]
                }
            }).draw();
        });

    </script>
</head>
<body>
<div id="canvasDiv"></div>
<div id="canvasDiv2"></div>
<div id="canvasDiv3"></div>
<div id="canvasDiv4"></div>
</body>
</html>