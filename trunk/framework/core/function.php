<?php
/*
*���ݿ���������
*/
function pdoconnects($dsn,$connmodel)
{    
   try {
	    $GLOBALS['pdolinks'][$connmodel]=new PDO($dsn['dsn'],$dsn['username'],$dsn['password']);
	    $GLOBALS['pdolinks'][$connmodel]->exec('SET CHARACTER SET '.$dsn['CHARACTER']);
	    return $GLOBALS['pdolinks'][$connmodel];
	  } catch (PDOException $e) {
       print "connects Error!: " . $e->getMessage() . "<br/>";
    }
}
/*
*���ݿ����Ӵ���
*/
function getConnect($table,$model=null,$connper=0)
{
	 $conn=$GLOBALS['config']['pdoconn'];
	 $tconn=array();
	 if(is_array($conn))
	 {
        foreach($conn as $k=>$v)
		{
		  if($k==$model||preg_match("|".$k."|i",$table)||preg_match("|".$k."|i",$model))
		  {
			 $prand=rand(0,count($v["master"])-1);
			 $connmodel=md5(json_encode($v["master"][$prand]));
			 if($GLOBALS['pdolinks'][$connmodel]!='')
			   $tconn['master']=$GLOBALS['pdolinks'][$connmodel];
			 else
			 {
			   $tconn['master']=pdoconnects($v["master"][$prand],$connmodel);
			 }
			$prand=rand(0,count($v["slaves"])-1);
			$connmodel=md5(json_encode($v["slaves"][$prand]));
			 if($GLOBALS['pdolinks'][$connmodel]!='')
			   $tconn['slaves']=$GLOBALS['pdolinks'][$connmodel];
			 else
			 {
			   $tconn['slaves']=pdoconnects($v["slaves"][$prand],$connmodel);
			 }
		  }
		}
	 }
	 if(count($tconn)<2)
	 {
		$prand=rand(0,count($conn['default']["master"])-1);
	    $connmodel=md5(json_encode($conn['default']["master"][$prand]));
		 if($GLOBALS['pdolinks'][$connmodel]!='')
		 {
			$tconn['master']=$GLOBALS['pdolinks'][$connmodel];
		 }else{
			$tconn['master']=pdoconnects($conn['default']["master"][$prand],$connmodel);
		 }
        $prand=rand(0,count($conn['default']["slaves"])-1);
		$connmodel=md5(json_encode($conn['default']["slaves"][$prand]));
		 if($GLOBALS['pdolinks'][$connmodel]!='')
		   $tconn['slaves']=$GLOBALS['pdolinks'][$connmodel];
		 else
		 {
		   $tconn['slaves']=pdoconnects($conn['default']["slaves"][$prand],$connmodel);
		 }
	 }
	 if($connper==1)
	 {
	   return array('master'=>$tconn['master'],'slaves'=>$tconn['slaves']);//����$model�������ӾͿ�����
	 }else if($connper==0)
	 {
	   return array('master'=>$tconn['master'],'slaves'=>$tconn['master']);//����$model�������ӾͿ�����
	 }
     
}
/*
*P()ȡ·������������lib plugin model view class
*/
function P($name)
{
 if(isset($GLOBALS['config'][$name])) return $GLOBALS['config'][$name];
 else return $GLOBALS['config']["frameworkpath"];
}
/*
*$config['key']ֵ��ȡ;
*/
function I($name)
{
  return $GLOBALS['config'][$name];
}
//J·����ת
function J()
{
   $arg = func_get_args();
   if(is_object($arg[0]))
	{
	  $controller=get_class($arg[0]);
	  $controller=substr($controller,0,-6);
	  C("router")->controller=$controller;	  
	  if($arg[1]=='') $arg[1]=$GLOBALS['config']['defaultindex'];      
	  C("router")->action=$arg[1];
	  array_shift($arg);
	  array_shift($arg);
	}else if(is_string($arg[0]))
	{
	  if($arg[1]=='')
	  {
	    C("router")->action=$arg[0];
		array_shift($arg);
	  }else if(is_array($arg[1])){
	    C("router")->action=$arg[0];
		array_shift($arg);
	  }else{
		C("router")->controller=$arg[0];
	    C("router")->action=$arg[1];
		array_shift($arg);
		array_shift($arg);
	  }
	}
	$router=R(C("router")->controller);
	if(method_exists($router,C("router")->action)) {
		$router->render(C("router")->action);
		call_user_func(array($router,C("router")->action),$arg);
	}
}
//C������
function C($class=null)
{
   if($class==null) return null;
   if(isset($GLOBALS[$class."class"]))
   {
     return $GLOBALS[$class."class"];
   }else{
     $GLOBALS[$class."class"]=new $class();
	 return $GLOBALS[$class."class"];
   }
}
//RΪ����Router
function R($router=null)
{
   if($router==null) return null;
   $router=$router."Router";
   if(isset($GLOBALS[$router]))
   {
     return $GLOBALS[$router];
   }else{
	 try{
        $GLOBALS[$router]=new $router();
	  }catch (PDOException $e) 
      {
        echo $e->getMessage();
      }
	 return $GLOBALS[$router];
   }
}
//MΪ�������ģ�ͣ���һ�ξͿ�ʼ�����ļ���
function M($table=null)
{
   if($table==null) return null;
   $table=$table."Model";
   if(isset($GLOBALS[$table]))
   {
     return $GLOBALS[$table];
   }else{
     $GLOBALS[$table]=new $table();
	 return $GLOBALS[$table];
   }
}
//��ʼ���������ļ����ļ���ʽ����mysql���ݿ��Զ��ѽṹд��ȥ
function initModelclass($modelname)
{
   $fix=substr($modelname,-4);
   if($fix=="Base") $modelname=substr($modelname,0,-4);
   $string="DESCRIBE ".$modelname;	
   $DB=getConnect($modelname);
	try{
	    $res=$DB['master']->query($string);
        $mate =$res->fetchAll(PDO::FETCH_ASSOC);  
	} catch (PDOException $e) 
        {
           echo $e->getMessage();
        }
   if(is_array($mate))
	 {
	   $newmodelstr="<?php \n class ".$modelname."Base extends model{ \n ";
	   $fields=array();
       $types=array();
	   $newmodelstr.="  var \$tablename='".$modelname."';";
	   foreach($mate as $key=>$value)
	   {
		  $value['Field']=strtolower($value['Field']);
	      if($value['Key']=='PRI')
		   {
             $newmodelstr.="\n var \$PRI='".$value['Field']."';";
	         if($value['Extra']=='auto_increment')
			   {
			     $newmodelstr.="\n var \$autoid=true;";
			   }else{
			     $newmodelstr.="\n var \$autoid=false;";
			   }
		   }
		  $fields[$value['Field']]=$value['Default'];
		  $types[$value['Field']]=$value['Type'];
	   }
	   $newmodelstr.="\n var \$fields=".var_export($fields,true).";";
	   $newmodelstr.="\n var \$types=".var_export($types,true).";";
	   $newmodelstr.="\n}\n?>";
	 }
	 file_put_contents(P("modelpath")."model/".$modelname.'Base.class.php',$newmodelstr);
}
/*
* �Զ�������
*/
function __autoload($class_name) {
    $fix=substr($class_name,-5);
	if($fix=='Model'){ //ģ�����ꡣ���Ҫ��Ϊ��new����model��
		$newc=substr($class_name,0,-5);		
		if(file_exists(P("webprojectpath")."model/".$class_name.".class.php"))
		{
		   require_once P("webprojectpath")."model/".$class_name.".class.php";
		   return;
		}elseif(file_exists(P("modelpath")."model/".$class_name.".class.php")){
		   require_once P("modelpath")."model/".$class_name.".class.php";	
		   return;
		}else{		   
           $newmodelstr="<?php \nclass ".$newc."Model extends ".$newc."Base{ \n ";
		   $newmodelstr.=" var \$mapper=array();\n";
		   $newmodelstr.=" var \$maps;\n";
		   $newmodelstr.=" var \$maparray=array();\n";
           $newmodelstr.=" \n} \n?>";
		   file_put_contents(P("modelpath")."model/".$newc.'Model.class.php',$newmodelstr);
		   require_once P("modelpath")."model/".$newc.'Model.class.php';
		   return;//�ӷ����Զ��˳��������
		}
	}	
	$fix=substr($class_name,-4);
	if($fix=='Base'){ //ģ�ͻ�����
		$newc=substr($class_name,0,-4);
		if(!file_exists(P("modelpath")."model/".$newc.'Base.class.php')&&!file_exists(P("webprojectpath")."model/".$newc.'Base.class.php'))
		{		   
		   initModelclass($newc);		
		   return;
		}
		if(file_exists(P("webprojectpath")."model/".$newc.'Base.class.php'))
		{		   
		   require_once P("webprojectpath")."model/".$newc.'Base.class.php';		
		   return;
		}elseif(file_exists(P("modelpath")."model/".$newc.'Base.class.php')){
		   require_once P("modelpath")."model/".$newc.'Base.class.php';	
		   return;
		}
	}
	$fix=substr($class_name,-6);
	if($fix=='Router'){ //·���ļ�
		$newc=substr($class_name,0,-6);
		if(file_exists(P("webprojectpath")."router/".$newc."Router.class.php"))
		{
		   require_once P("webprojectpath")."router/".$newc."Router.class.php";
		   return;
		}elseif(file_exists(P("routerpath")."router/".$newc."Router.class.php")){
		   require_once P("routerpath")."router/".$newc."Router.class.php";	
		   return;
		}
	}
	if(isset($GLOBALS['config']['frameworklib'][$class_name])){
		require_once $GLOBALS['config']['frameworklib'][$class_name];
	    return;
	}
	if(file_exists(P("webprojectpath")."class/".$class_name.'.class.php'))
	{		   
	  require_once P("webprojectpath")."class/".$class_name.'.class.php';	
	  return;
	}
	if(file_exists(P("frameworkpath")."class/".$class_name.'.class.php'))
	{		   
	  require_once P("frameworkpath")."class/".$class_name.'.class.php';
	  return;
	}
	if(file_exists(P("frameworkpath").$class_name.'.php'))
	{		   
	  require_once P("frameworkpath").$class_name.'.php';
	  return;
	}
	if(is_array($GLOBALS['config']['frameworklib']))
	{
	  foreach($GLOBALS['config']['frameworklib'] as $k=>$v)
	  {
		if(is_numeric($k))
		{
		   if(preg_match("@".$class_name."\.(class\.)?php$@i",$v)){ require_once $v; return; }
		}
	  }
	}
}
/*
* URL����������Ҫ���ǵ�ruleMaps���õ� ��Ϊ��ַ��ʾ�Ǳ���
* ��ȡruleMaps �ͳ�ʼ�����$controller
*/
function url_for()
{
  $arg_list = func_get_args();
  return $arg_list[0];
}
?>