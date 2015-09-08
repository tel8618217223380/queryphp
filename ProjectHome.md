php微型智能框架主要参考了
国内和国外的框架
想把写php程序像写jquery一样写法

这个一个微型框架
可以实现MVC方式

支持path\_info方式

控制动作在router目录下面
/default/index
调用router目录下面defaultRouter.class.php文件
取得类后调用index方式
J()是index方法跳转
R()是由控制
C()是生成类
M()是数据库类模型 数据库链接在model.function.php里面设置
```
        $supply->copyRecord()->save(M("booktype")); //自动会从$supply中取得关联值赋给M("booktype");

	$supply->Books=array("classname"=>"星际解霸5"); //支持两个表两个主键之间互联
	print_r($supply->save());                       //支持三个字段关系影射

	//$supply->where($supply->PRI.">12")->delete();
	//$supply->save();
	$books=M("booktype");
	//M("booktype")->where($books->PRI.">12")->delete();
        $supply->Books=array("classname"=>"星际解霸21");//各种插入数据方式
	$supply->Books=array("classname"=>"星际解霸22");
	$supply->Books=array("0"=>array("classname"=>"星际解霸88"),2=>array("classname"=>"星际解霸98"));
	print_r($supply->data);
	$supply->copyRecord()->save();
```