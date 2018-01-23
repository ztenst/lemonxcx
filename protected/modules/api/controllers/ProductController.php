<?php
class ProductController extends ApiController
{
	public function actionList()
	{
		$data = $data['list'] = [];
		$area = (int)Yii::app()->request->getQuery('area',0);
		$street = (int)Yii::app()->request->getQuery('street',0);
		$type = Yii::app()->request->getQuery('py','');
		$cid = (int)Yii::app()->request->getQuery('cid',0);
		$fid = (int)Yii::app()->request->getQuery('fid',0);
		$mid = (int)Yii::app()->request->getQuery('mid',0);
		$ccmid = (int)Yii::app()->request->getQuery('ccmid',0);
		$cadrid = (int)Yii::app()->request->getQuery('cadrid',0);
		$uid = (int)Yii::app()->request->getQuery('uid',0);
		$save = (int)Yii::app()->request->getQuery('save',0);
		$order = (int)Yii::app()->request->getQuery('order',0);
		$page = (int)Yii::app()->request->getQuery('page',1);
		$limit = (int)Yii::app()->request->getQuery('limit',20);
		$kw = $this->cleanXss(Yii::app()->request->getQuery('kw',''));
		!$page && $page = 1;
		$criteria = new CDbCriteria;
		$criteria->order = 'sort desc,updated desc';
		$criteria->limit = $limit;
		if($kw) {
			$criteria->addSearchCondition('name',$kw);
		}
		// if($cid) {
		// 	$criteria->addCondition("cid=:cid");
		// 	$criteria->params[':cid'] = $cid;
		// }
		foreach (['street','type','cid','fid','mid','ccmid','cadrid','area'] as $key => $value) {
			if($$value) {
				// var_dump($value,$$value);exit;
				$criteria->addCondition("$value=:$value");
				$criteria->params[":$value"] = $$value;
			}
		}
		if($save&&$uid) {
			$ids = [];
			$saeids = Yii::app()->db->createCommand("select pid from save where uid=$uid")->queryAll();
			if($saeids) {
				foreach ($saeids as $key => $value) {
					$ids[] = $value['pid'];
				}
			}
			$criteria->addInCondition('id',$ids);
		}
		if($order&&$uid) {
			$ids = [];
			$saeids = Yii::app()->db->createCommand("select pid from `order` where uid=$uid")->queryAll();
			if($saeids) {
				foreach ($saeids as $key => $value) {
					$ids[] = $value['pid'];
				}
			}
			$criteria->addInCondition('id',$ids);
		}
		$ress = ProductExt::model()->normal()->getList($criteria,$limit);
		$infos = $ress->data;
		$pager = $ress->pagination;
		if($infos) {
			foreach ($infos as $key => $value) {
				$data['list'][] = [
					'id'=>$value->id,
					'name'=>Tools::u8_title_substr($value->name,20),
					'price'=>$value->price,
					'old_price'=>$value->old_price,
					'ts'=>$value->shortdes,
					'image'=>ImageTools::fixImage($value->image,370,250),
				];
			}
		}
		$data['num'] = $pager->itemCount;
		$data['page_count'] = $pager->pageCount;
		$data['page'] = $page;
		if(!isset($data['list']))
			$data['list'] = [];
		$this->frame['data'] = $data;
	}

	public function actionInfo($id='',$openid='')
	{
		$info = ProductExt::model()->findByPk($id);
		$data = $info->attributes;
		$images = $info->images;
		if($images) {
			foreach ($images as $key => $value) {
				$data['images'][] = ImageTools::fixImage($value->url); 
			}
		}
		$data['is_save'] = 0;
		if($openid) {
			$user = UserExt::getUserByOpenId($openid);
			if($uid = $user->id) {
				$data['is_save'] = SaveExt::model()->count("pid=$id and uid=$uid")?1:0;
			}
		}
		if($confs = $info->data_conf) {
			$fields = Yii::app()->file->getFields();
			$confs = json_decode($confs,true);
			$ids = $tagname = [];
			foreach ($confs as $key => $value) {
				$ids[] = $value;
			}
			$criteria = new CDbCriteria;
			$criteria->select = 'id,name';
			$criteria->addInCondition('id',$ids);

			$tags = TagExt::model()->findAll($criteria);
			if($tags) {
				foreach ($tags as $key => $value) {
					$tagname[$value['id']] = $value['name'];
				}
			}
			// var_dump($tags[0]['attributes']);exit;
			foreach ($confs as $key => $value) {
				$data['params'][$fields[$key]] = $tagname[$value];
			}
			$data['created'] = date('Y-m-d',$data['created']);
			$data['updated'] = date('Y-m-d',$data['updated']);
			
		}
		$this->frame['data'] = $data;
	}

	public function actionGetCates()
	{
		$data = [];
		$ress = TagExt::model()->normal()->findAll("cate='pcate'");
		if($ress) {
			foreach ($ress as $key => $value) {
				$data[] = ['id'=>$value->id,'name'=>$value->name];
			}
		}
		$this->frame['data'] = $data;
	}

	public function actionAddOrder()
	{
		$data['pid'] = Yii::app()->request->getPost('pid','');
        $data['username'] = Yii::app()->request->getPost('username','');
        $data['note'] = Yii::app()->request->getPost('note','');
        $data['phone'] = Yii::app()->request->getPost('phone','');
        $form_id = Yii::app()->request->getPost('form_id','');
        $openid = Yii::app()->request->getPost('openid','');

        if(!$data['pid']||!$openid) {
        	$this->returnError('参数错误');
        } else {
        	$product = ProductExt::model()->findByPk($data['pid']);
        	$product && $data['pname'] = $product->name;
        }
        if($user = UserExt::getUserByOpenId($openid)) {
        	$data['uid'] = $user->id;
        	$user->true_name = $data['username'];
        	$user->phone = $data['phone'];
        	$user->save();
        }
        $order = new OrderExt;
		$order->attributes = $data;
		if(!$order->save()) {
            $this->returnError(current(current($order->getErrors())));
        } else {
        	// $appid=SiteExt::getAttr('qjpz','appid');
	        // $apps=SiteExt::getAttr('qjpz','apps');
	        // if(!$appid||!$apps) {
	        //     return '';
	        // }
	        // $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$apps";
	        // $res = HttpHelper::getHttps($url);
        	// // $this->returnError($this->getAt());
        	// // var_dump($this->getAt());exit;
        	// // Yii::log($this->getAt());
        	// $this->sendMsg($form_id,$product->name,$data['username'],$data['phone'],$data['note']);
        }
	}	

    public function actionAddSave($pid='',$openid='')
    {
        if($pid&&$openid) {
            $staff = UserExt::getUserByOpenId($openid);
            if($save = SaveExt::model()->find('pid='.(int)$pid.' and uid='.$staff->id)) {
                SaveExt::model()->deleteAllByAttributes(['pid'=>$pid,'uid'=>$staff->id]);
                $this->returnSuccess('取消收藏成功');
            } else {
                $save = new SaveExt;
                $save->uid = $staff->id;
                $save->pid = $pid;
                $save->save();
                $this->returnSuccess('收藏成功');
            }
        }else {
            $this->returnError('请登录后操作');
        }
    }

    public function sendMsg($form_id,$pname,$username,$phone,$note)
    {
    	if($token = $this->getAt()) {
    		$openid = SiteExt::getAttr('qjpz','openid');
	    	$temid = SiteExt::getAttr('qjpz','temid');
	    	if($openid&&$temid) {
	            // $token = $this->getAT();
	            $data['touser'] = $openid;
	            $data['template_id'] = $temid;
	            $data['form_id'] = $form_id;
	            $data['page'] = '';
	            $data['data']['keyword1']['color'] = '';
	            $data['data']['keyword2']['color'] = '';
	            $data['data']['keyword3']['color'] = '';
	            $data['data']['keyword4']['color'] = '';
	            $data['data']['keyword1']['value'] = $pname;
	            $data['data']['keyword2']['value'] = $username;
	            $data['data']['keyword3']['value'] = $phone;
	            $data['data']['keyword4']['value'] = $note;
	            $data['emphasis_keyword'] = '';
	            $posturl = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=$token";
	            // var_dump($posturl,$data);exit;
	            // Yii::log($posturl);
	            // Yii::log(json_encode($data));
	            $res = json_decode(HttpHelper::vpost($posturl,json_encode($data)),true);
	            Yii::log(json_encode($res));
	            // $this->frame['data'] = $res['content'];
	        }
    	}
    }

    public function getAT()
    {
    	// $appid=SiteExt::getAttr('qjpz','appid');
     //    $apps=SiteExt::getAttr('qjpz','apps');
     //    if(!$appid||!$apps) {
     //        return '';
     //    }
     //    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$apps";
     //    $res = HttpHelper::getHttps($url);
     //    if($res&&$res['content']) {
     //        $data = json_decode($res['content'],true);
     //        return $data['access_token'];
     //    }
    	$data = Yii::app()->cache->get('accToken') ? Yii::app()->cache->get('accToken') : (object)array('expire_time'=>0,'data'=>'');
    	$ticket = '';
        if ($data->expire_time < time()) {
            $accessToken = $this->getATNow();
            Yii::log($accessToken);
            if($accessToken) {
            	$data->expire_time = time() + 7000;
                $ticket = $data->data = $accessToken;
            	Yii::app()->cache->set('accToken', $data, 7000);
            }
        } else {
            $ticket = $data->data;
        }
        return $ticket;
    }

    public function getATNow()
    {
    	$appid=SiteExt::getAttr('qjpz','appid');
        $apps=SiteExt::getAttr('qjpz','apps');
        if(!$appid||!$apps) {
            return '';
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$apps";
        $res = HttpHelper::getHttps($url);
        $data = json_decode($res['content'],true);
		return $data['access_token'];
    }

}