<!DOCTYPE HTML>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0,user-scalable=no">
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<title>Hello,world</title>
<style type="text/css">
html{height:100%}
body{height:100%;margin:0px;padding:0px}
#container{height:100%}
</style>
<script type="text/javascript"
src="http://webapi.amap.com/maps?v=1.3&key=6bc2612f8c4254f6631a39c76850cebb">
</script>
<script type="text/javascript">
function initialize(){
  var position=new AMap.LngLat(116.397428,39.90923);
  var mapObj=new AMap.Map("container",{
  view: new AMap.View2D({//������ͼ��ά�ӿ�
  center:position,//�������ĵ�����
  zoom:14, //���õ�ͼ���ż���
  rotation:0 //���õ�ͼ��ת�Ƕ�
 }),
 lang:"zh_cn"//���õ�ͼ�������ͣ�Ĭ�ϣ����ļ���
});//������ͼʵ��
}
</script>
</head>
 
<body onload="initialize()">
<div id="container" style="width:200px;height:200px;"></div>
</body>
</html>