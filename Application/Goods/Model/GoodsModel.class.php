<?php

namespace Goods\Model;

use Think\Model;

class GoodsModel extends Model {

    protected $_validate = array(
        array('goods_name', 'require', '商品名称不能为空', 1),
        array('market_price', 'require', '市场价不能为空', 1),
        array('shop_price', 'require', '本店价不能为空', 1),
        // array('logo','require','logo不能为空',1),
        array('is_on_sale', 'require', '是否上架不能为空', 1),
        array('goods_name', '', '商品名称不能重复', 1, 'unique'),
        );

    //生成分页
    public function search() {
        $pages = 10;
        $where = 1;
        $count = $this->where($where)->count(); // 查询满足要求的总记录数
        $Page = new \Think\Page($count, $pages); // 实例化分页类 传入总记录数和每页显示的记录数(25)
        $data['show'] = $Page->show(); // 分页显示输出
        //查询商品信息,并取出库存量
        $data['list'] = $this->alias('a')->field('a.*,sum(b.goods_number) goods_number')->join('left join sh_product b on a.id=b.goods_id')->order('id')->group('a.id')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        return $data;
    }

//根据商品id查询当前商品实际存在的单选属性
    public function product($goods_id){
        $sql="select a.attr_id,b.attr_name,a.attr_value,a.id from sh_goods_attr a left join sh_attribute b on a.attr_id=b.id where $goods_id=a.goods_id and b.attr_type='单选'";
        $result=$this->query($sql);
        $arr=array();
        foreach($result as $k=>$v){
        //把attr_id属性相同的放到一起    
            $arr[$v['attr_id']][]=$v;

        }


        return $arr;

    }

    public function _before_insert(&$data, $options) {

        if (!empty($_FILES["logo"]["name"])) {//添加商品之前先上传图片生成4张格式图片

            $upload = new \Think\Upload();
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './Public/Uploads/';
            $upload->savePath = 'Goods/';
            $info = $upload->upload(array('logo' => $_FILES["logo"]));
            
            $image = new \Think\Image();
            $image_path = './Public/Uploads/' . $info["logo"]["savepath"] . $info["logo"]["savename"];
            $big_img = $info["logo"]["savepath"] . 'big_' . $info["logo"]["savename"];
            $mid_img = $info["logo"]["savepath"] . 'mid_' . $info["logo"]["savename"];
            $small_img = $info["logo"]["savepath"] . 'small_' . $info["logo"]["savename"];
            
            
            $image->open($image_path);

            $image->thumb(C('GOODS_IMG_BIG_WIDTH'), C('GOODS_IMG_BIG_HEIGHT'))->save('./Public/Uploads/' . $big_img); //将原图覆盖掉
            $image->thumb(C('GOODS_IMG_MID_WIDTH'), C('GOODS_IMG_MID_HEIGHT'))->save('./Public/Uploads/' . $mid_img); //将原图覆盖掉
            $image->thumb(C('GOODS_IMG_SM_WIDTH'), C('GOODS_IMG_SM_HEIGHT'))->save('./Public/Uploads/' . $small_img); //将原图覆盖掉
            //将地址保存到数据库中
            $data['logo'] = $info["logo"]["savepath"] . $info["logo"]["savename"];
            $data['sm_logo'] = $small_img;
            $data['mid_logo'] = $mid_img;
            $data['big_logo'] = $big_img;

        }
        $data['goods_sn'] = time() . mt_rand(111111, 999999);//给商品添加货号
    }

    public function _after_insert($data, $options) {
        $mt = I('post.mp');//添加商品之后,可以取到商品的id,然后将会员信息表的数据插入进memberPrice表
        $memberPrice = M('memberPrice');

        foreach ($mt as $key => $m) {
            if (!empty($m)) {
                $afters = $memberPrice->add(
                    array(
                        'price' => $m,
                        'level_id' => $key,
                        'goods_id' => $data['id']
                        )
                    );
            }
        }

        if ($this->_hasImage($_FILES['goods_pic']['tmp_name'])) {//判断是否在添加商品的后置钩子里,有上传商品图片,有就
            //为该商品生成3种格式的图片

            $upload = new \Think\Upload();
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './Public/Uploads/';
            $upload->savePath = 'Goods/';
            $info = $upload->upload(array('goods_pic' => $_FILES['goods_pic']));
            $image = new \Think\Image();
            $big_width = C('GOODS_IMG_BIG_WIDTH');
            $big_height = C('GOODS_IMG_BIG_HEIGHT');

            $sm_width = C('GOODS_IMG_SM_WIDTH');
            $sm_height = C('GOODS_IMG_SM_HEIGHT');
            $goods_pic = M('goodsPic');
            foreach ($info as $i) {
                $image_path = './Public/Uploads/' . $i["savepath"] . $i["savename"];
                $big_img = $i["savepath"] . 'big_' . $i["savename"];

                $small_img = $i["savepath"] . 'small_' . $i["savename"];
                $image->open($image_path);
                $image->thumb($big_width, $big_height)->save('./Public/Uploads/' . $big_img); //将原图覆盖掉

                $image->thumb($sm_width, $sm_height)->save('./Public/Uploads/' . $small_img); //将原图覆盖掉    
                //将地址保存到数据库中
                $goods_pic->goods_id = $data['id'];
                $goods_pic->logo = $i["savepath"] . $i["savename"];
                $goods_pic->sm_logo = $small_img;

                $goods_pic->big_logo = $big_img;
                $goods_pic->add();
            }
        }
       //将商品属性的信息接收到,并存入商品属性表中
        $goods_attr = I('post.goods_attr');
        $attr_price = I('post.attr_price');

        if ($goods_attr) {
            $attr = M('goodsAttr');
            $i = 0;
            foreach ($goods_attr as $k => $g) {
                if (is_array($g)) {
                    foreach ($g as $gg) {
                        if (!empty($gg)) {
                            $attr->add(array(
                                'goods_id' => $data['id'],
                                'attr_id' => $k,
                                'attr_value' => $gg,
                                'attr_price' => $attr_price[$i]
                                ));
                        }
                        $i++;
                    }
                } else {
                    $attr->add(array(
                        'goods_id' => $data['id'],
                        'attr_id' => $k,
                        'attr_value' => $g,
                        'attr_price' => $attr_price[$i]
                        ));
                    $i++;
                }
            }
        }
        //接收商品的推荐到这个数组
        $rec=I('post.rec');
        if($rec){
            $rec_item=M('recommendItem');
            foreach($rec as $r){
             $rec_item->add(array(
                 'rec_id'=>$r['id'],
                 'goods_id'=>$data['id']
                 )); 
         }

     }
 }
    //判断有一张图片上传即为真
 private function _hasImage($files) {
    foreach ($files as $k => $v) {
        if ($v)
            return TRUE;
    }
    return FALSE;
}

public function _before_delete($options) {
        //判断是否为批量删除商品,是则批量将商品缩略图删除掉
    if (is_array($options["where"]["id"])) {
     $pics = $this->field("logo,sm_logo,mid_logo,big_logo")->where('id in(' . $options["where"]["id"][1].')')->select();

     foreach($pics as $p){
        unlink('Public/Uploads/' . $p['logo']);
        unlink('Public/Uploads/' . $p['sm_logo']);
        unlink('Public/Uploads/' . $p['mid_logo']);
        unlink('Public/Uploads/' . $p['big_logo']);   
    }
    $member_price = M('memberPrice');
    $member_price->where('goods_id in(' . $options["where"]["id"][1].')')->delete();

    $goods_attr = M('goodsAttr');
    $goods_attr->where('goods_id in(' . $options["where"]["id"][1].')')->delete();

    $goods_pic = M('goodsPic');
    $goods_pic_data = $goods_pic->field("logo,sm_logo,big_logo")->where('goods_id in(' . $options["where"]["id"][1].')')->select();
    $goods_pic->where('goods_id in(' . $options["where"]["id"][1].')')->delete();
    foreach($goods_pic_data as $g){
        unlink('Public/Uploads/' . $g['logo']);
        unlink('Public/Uploads/' . $g['sm_logo']);
        unlink('Public/Uploads/' . $g['big_logo']);         
    }
        } else {//单独删除商品时,将商品图片删除
            $pics = $this->field("logo,sm_logo,mid_logo,big_logo")->where('id=' . $options["where"]["id"])->find();
            unlink('Public/Uploads/' . $pics['logo']);
            unlink('Public/Uploads/' . $pics['sm_logo']);
            unlink('Public/Uploads/' . $pics['mid_logo']);
            unlink('Public/Uploads/' . $pics['big_logo']);

            $member_price = M('memberPrice');
            $member_price->where('goods_id=' . $options["where"]["id"])->delete();

            $goods_attr = M('goodsAttr');
            $goods_attr->where('goods_id=' . $options["where"]["id"])->delete();

            $goods_pic = M('goodsPic');
            $goods_pic_data = $goods_pic->field("logo,sm_logo,big_logo")->where('goods_id=' . $options["where"]["id"])->select();
            $goods_pic->where('goods_id=' . $options["where"]["id"])->delete();
            unlink('Public/Uploads/' . $goods_pic_data['logo']);
            unlink('Public/Uploads/' . $goods_pic_data['sm_logo']);
            unlink('Public/Uploads/' . $goods_pic_data['big_logo']);       
        }
    }

    public function _before_update(&$data, $options) {
    //修改商品时,判断是否有修改缩略图图片,有的话先将原图全删掉。
     if (!empty($_FILES["logo"]["name"])) {
         $goods_pics=$this->where('id='.$options['where']['id'])->find();
         unlink('Public/Uploads/'.$goods_pics['logo']);
         unlink('Public/Uploads/' .$goods_pics['sm_logo']);
         unlink('Public/Uploads/' .$goods_pics['mid_logo']);
         unlink('Public/Uploads/' .$goods_pics['big_logo']);
         $upload = new \Think\Upload();
         $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
         $upload->rootPath = './Public/Uploads/';
         $upload->savePath = 'Goods/';
         $info = $upload->upload(array('logo' => $_FILES["logo"]));

         $image = new \Think\Image();
         $image_path = './Public/Uploads/' . $info["logo"]["savepath"] . $info["logo"]["savename"];
         $big_img = $info["logo"]["savepath"] . 'big_' . $info["logo"]["savename"];
         $mid_img = $info["logo"]["savepath"] . 'mid_' . $info["logo"]["savename"];
         $small_img = $info["logo"]["savepath"] . 'small_' . $info["logo"]["savename"];

         $image->open($image_path);

            $image->thumb(C('GOODS_IMG_BIG_WIDTH'), C('GOODS_IMG_BIG_HEIGHT'))->save('./Public/Uploads/' . $big_img); //将原图覆盖掉
            $image->thumb(C('GOODS_IMG_MID_WIDTH'), C('GOODS_IMG_MID_HEIGHT'))->save('./Public/Uploads/' . $mid_img); //将原图覆盖掉
            $image->thumb(C('GOODS_IMG_SM_WIDTH'), C('GOODS_IMG_SM_HEIGHT'))->save('./Public/Uploads/' . $small_img); //将原图覆盖掉
            //将地址保存到数据库中
            $data['logo'] = $info["logo"]["savepath"] . $info["logo"]["savename"];
            $data['sm_logo'] = $small_img;
            $data['mid_logo'] = $mid_img;
            $data['big_logo'] = $big_img;


        }

       //修改商品时接收提交的会员价格信息
        $mt=I('post.mp');
        $member_price=M('memberPrice');
        foreach($mt as $k=>$m){
         if(trim($m)==''){
             continue;
         }else{
             $member_price->where("level_id=$k and goods_id={$options['where']['id']}")->delete();  
             $member_price->add(array(
                'goods_id'=>$options['where']['id'],
                'level_id'=>$k,
                'price'=>$m
                ));   
         }
     }
       //判断是否有修改商品图片
     if ($this->_hasImage($_FILES['goods_pic']['tmp_name'])) {
        $upload = new \Think\Upload();
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Public/Uploads/';
        $upload->savePath = 'Goods/';
        $info = $upload->upload(array('goods_pic' => $_FILES['goods_pic']));
        $image = new \Think\Image();
        $big_width = C('GOODS_IMG_BIG_WIDTH');
        $big_height = C('GOODS_IMG_BIG_HEIGHT');

        $sm_width = C('GOODS_IMG_SM_WIDTH');
        $sm_height = C('GOODS_IMG_SM_HEIGHT');
        $goods_pic = M('goodsPic');
        foreach ($info as $i) {
            $image_path = './Public/Uploads/' . $i["savepath"] . $i["savename"];
            $big_img = $i["savepath"] . 'big_' . $i["savename"];

            $small_img = $i["savepath"] . 'small_' . $i["savename"];
            $image->open($image_path);
                $image->thumb($big_width, $big_height)->save('./Public/Uploads/' . $big_img); //将原图覆盖掉

                $image->thumb($sm_width, $sm_height)->save('./Public/Uploads/' . $small_img); //将原图覆盖掉    
                //将地址保存到数据库中
                $goods_pic->goods_id = $options['where']['id'];
                $goods_pic->logo = $i["savepath"] . $i["savename"];
                $goods_pic->sm_logo = $small_img;

                $goods_pic->big_logo = $big_img;
                $goods_pic->add();
            }
        }

              //将新商品属性的信息接收到,并存入商品属性表中
        $goods_attr = I('post.goods_attr');
        $attr_price = I('post.attr_price');
        if ($goods_attr) {
            $attr = M('goodsAttr');
            $i = 0;
            foreach ($goods_attr as $k => $g) {
                if (is_array($g)) {
                    foreach ($g as $gg) {
                        if (!empty($gg)) {
                            $attr->add(array(
                                'goods_id' => $options['where']['id'],
                                'attr_id' => $k,
                                'attr_value' => $gg,
                                'attr_price' => $attr_price[$i]
                                ));
                        }
                        $i++;
                    }
                } else {
                    $attr->add(array(
                        'goods_id' => $options['where']['id'],
                        'attr_id' => $k,
                        'attr_value' => $g,
                        'attr_price' => $attr_price[$i]
                        ));
                    $i++;
                }
            }
        }

       //old_开头的商品的属性修改
        $goods_attr = I('post.old_goods_attr');
        $attr_price = I('post.old_attr_price');
        $keys=array_keys($attr_price);
        
        $values=  array_values($attr_price);
        if ($goods_attr) {
            $attr = M('goodsAttr');
            $i = 0;
            foreach ($goods_attr as $k => $g) {
                if (is_array($g)) {
                    foreach ($g as $gg) {
                        if (!empty($gg)) {
                            $attr->where('id='.$keys[$i])->save(array(

                                'attr_value' => $gg,
                                'attr_price' => $values[$i]
                                ));
                        }
                        $i++;
                    }
                } else {
                    $attr->where('id='.$keys[$i])->save(array(

                        'attr_value' => $g,
                        'attr_price' => $values[$i]
                        ));
                    $i++;
                }
            }
        }
        //修改推荐位后,处理表单
        $rec=I('post.rec');
        if($rec){
            $rec_item=M('recommendItem');
            $rec_item->where('goods_id='.$options['where']['id'].' and rec_id in(select id from sh_recommend where rec_type="商品")')->delete();
            foreach($rec as $r){
              $rec_item->add(array(
                  'goods_id'=>$options['where']['id'],
                  'rec_id'=>$r
                  ));
          }


      }
  }
}
