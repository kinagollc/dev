<div id="chartdiv"></div>

<?php 
$datas=json_encode($data);
?>
<script type="text/javascript">
var chart = AmCharts.makeChart("chartdiv", {
    "type": "serial",
	"theme": "light",
    "legend": {    	
    	"align":"left"
        //"useGraphSettings": true
    },
    "dataProvider":<?php echo $datas;?>,
    "valueAxes": [{
        "stackType": "regular",
        "axisAlpha": 0.3,
        "gridAlpha": 0
    }],
    "graphs": [{
    	"lineColor": "#2c9f2c",
        "balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
        "fillAlphas": 0.8,
        "labelText": "[[value]]",
        "lineAlpha": 0.3,
        "title": "<?php echo Driver::t("Successful")?>",
        "type": "column",		
        "valueField": "successful"
    }, {
    	"lineColor": "#e53935",
        "balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
        "fillAlphas": 0.8,
        "labelText": "[[value]]",
        "lineAlpha": 0.3,
        "title": "<?php echo Driver::t("Failed")?>",
        "type": "column",
        "valueField": "failed"
    },{
    	"lineColor": "#f6bf00",
        "balloonText": "<b>[[title]]</b><br><span style='font-size:14px'>[[category]]: <b>[[value]]</b></span>",
        "fillAlphas": 0.8,
        "labelText": "[[value]]",
        "lineAlpha": 0.3,
        "title": "<?php echo Driver::t("Cancelled")?>",
        "type": "column",		
        "valueField": "cancelled"
    }],
    "categoryField": "driver_name",
    "categoryAxis": {
        "gridPosition": "start",
        "axisAlpha": 0,
        "gridAlpha": 0,
        "position": "left",
       "labelRotation": 45
    },
    "export": {
    	"enabled": true
     }

});

</script>