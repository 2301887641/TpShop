<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>ECSHOP 管理中心 - 商品列表 </title>
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link href="/Public/Styles/general.css" rel="stylesheet" type="text/css" />
    <link href="/Public/Styles/main.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="/Public/Js/jquery.js"></script>
</head>
<body>
    <h1>
        <span class="action-span"><a href="/Goods/Goods/lst">商品列表</a></span>
        <span class="action-span1"><a href="#">ECSHOP 管理中心</a></span>
        <span id="search_id" class="action-span1"> - 货品 列表 </span>
        <div style="clear:both"></div>
    </h1>

    <form method="post" action="/Goods/Goods/product/goods_id/73" name="listForm">
        <div class="list-div" id="listDiv">
            <table cellpadding="3" cellspacing="1">
                <tr>
                    <?php $count=0;?>
                    <?php foreach($attrs as $k=>$v): $count++; ?>
                    <th width="80"><?php echo $v[0]['attr_name']?></th>
                <?php endforeach;?>
                <th>库存量</th>
                <th>操作</th>
            </tr>
            <?php if($product):?>
            <?php foreach($product as $k0=>$v0):?>    
                <tr>
                    <?php foreach($attrs as $k=>$v):?> 
                        <td align="center">
                            <!-- 这里name属性值是属性的id数组 -->
                            <select name="goods_attr[<?php echo $k?>][]">
                                <option value="">请选择</option>
                                <?php foreach($v as $k2=>$v2): if(strpos(','.$v0["goods_attr"].',',','.$v2["id"].',')!==false) $select="selected='selected'"; else $select=""; ?>

                                    <option <?php echo $select;?>   value="<?php echo $v2["id"];?>"> <?php echo $v2["attr_value"];?></option> 
                                <?php endforeach;?> 
                            </select>
                        </td>   
                    <?php endforeach;?>
                    <td width="150" align="center"><input type="text" name="goods_number[]" value="<?php echo $v0["goods_number"];?>"></td>
                    <td width="50" align="center"><input type="button" onclick="addTr(this)" value="<?php echo $a=$k0===0?"+":"-";?>"></td>
                </tr>
            <?php endforeach;?>    
            <?php else:?>
                <tr>
                    <?php foreach($attrs as $k=>$v):?> 
                        <td align="center">
                            <!-- 这里name属性值是属性的id数组 -->
                            <select name="goods_attr[<?php echo $k?>][]">
                                <option value="">请选择</option>
                                <?php foreach($v as $k2=>$v2):?>
                                    <option value="<?php echo $v2["id"];?>"><?php echo $v2["attr_value"];?></option>     
                                <?php endforeach;?> 
                            </select>
                        </td>   
                    <?php endforeach;?>
                    <td width="150" align="center"><input type="text" name="goods_number[]"></td>
                    <td width="50" align="center"><input type="button" onclick="addTr(this)" value="+"></td>
                </tr>  
            <?php endif;?>    
            <tr><td  align="center" colspan="<?php echo $count+2?>"><input type="submit" value="提交"></td></tr>
        </table>
    </div>
</form>

<div id="footer">
    共执行 3 个查询，用时 0.021251 秒，Gzip 已禁用，内存占用 2.194 MB<br />
    版权所有 &copy; 2005-2012 高端大气上档次有限公司，并保留所有权利。</div>
</body>
</html>
<script>
    $(".first_check").click(function(){
        if($(this).attr("checked")){
            $(".second_check").attr("checked","checked");
        }else{
           $(".second_check").removeAttr("checked"); 

       }

   });

//点击+号增加一行,点击-号减少一行
function addTr(that){
    var parent=$(that).parent().parent();
    console.log(parent);
    //判断如果是加号就增加一行    
    if($(that).val()=="+"){
     var trClone=parent.clone();
     trClone.insertAfter(parent);
       //同时将input类型为button,的value置为-
       trClone.find("input:button").val("-");
   }else{
    parent.remove();

}


}    

</script>