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
		$savetype = (int)Yii::app()->request->getQuery('savetype',0);
		$order = (int)Yii::app()->request->getQuery('order',0);
		$rz = (int)Yii::app()->request->getQuery('rz','');
		$page = (int)Yii::app()->request->getQuery('page',1);
		$limit = (int)Yii::app()->request->getQuery('limit',20);
		$status = Yii::app()->request->getQuery('status',1);
		$sort = Yii::app()->request->getQuery('sort',0);
		$kw = $this->cleanXss(Yii::app()->request->getQuery('kw',''));
		!$page && $page = 1;
		$criteria = new CDbCriteria;
		$criteria->addCondition("deleted=0");
		if(!$sort) {
			$criteria->order = 'sort desc,updated desc';
		} elseif ($sort==1) {
			$criteria->order = 'price asc,sort desc,updated desc';
		} elseif ($sort==2) {
			$criteria->order = 'price desc,sort desc,updated desc';
		} elseif ($sort==3) {
			$criteria->order = 'created desc,sort desc,updated desc';
		}
		
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
		if($savetype&&$save&&$uid) {
			$ids = [];
			$saeids = Yii::app()->db->createCommand("select pid from save where uid=$uid and type=$savetype")->queryAll();
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
		if($uid&&!$save||$uid&&!$order) {
			$criteria->addCondition('uid='.$uid);
		}
		if(is_numeric($status)) {
			$criteria->addCondition('status='.$status);
		}
		if($rz) {
			$criteria->addCondition('is_rz='.$rz);
		}
		$ress = ProductExt::model()->getList($criteria,$limit);
		$infos = $ress->data;
		$pager = $ress->pagination;
		if($infos) {
			$rzwords = SiteExt::getAttr('qjpz','rzwords');
			foreach ($infos as $key => $value) {
				$data['list'][] = [
					'id'=>$value->id,
					'name'=>Tools::u8_title_substr($value->name,20),
					'rzwords'=>$value->is_rz?$rzwords:'',
					'status'=>$value->status,
					'status_word'=>ProductExt::$status[$value->status],
					'company'=>$value->company,
					'price'=>$value->price,
					'hits'=>$value->hits,
					'ts'=>Tools::u8_title_substr($value->shortdes,30),
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

	public function actionInfo($id='',$openid='',$uid='')
	{
		$info = ProductExt::model()->findByPk($id);
		if(!$info) {
			$this->returnError('产品不存在');
		} else {
			$info->hits += 1;
			$info->save();
			$obj = new LogExt;
			$obj->pid = $id;
			$obj->uid = $uid;
			$obj->type = 1;
			$obj->save();
		}
		$data = $info->attributes;
		$images = $info->images;
		$data['images'][] = ImageTools::fixImage($info->image); 
		$data['imgs'][] = $info->image; 
		if($images) {
			foreach ($images as $key => $value) {
				$data['images'][] = ImageTools::fixImage($value->url); 
				$data['imgs'][] = $value->url; 
			}
		}
		$data['is_save'] = 0;
		if($openid) {
			$user = UserExt::getUserByOpenId($openid);
			if($uid = $user->id) {
				$data['is_save'] = SaveExt::model()->count("pid=$id and uid=$uid")?1:0;
			}
		}
		if($uid) {
			// $user = UserExt::getUserByOpenId($openid);
			// if($uid = $user->id) {
				$data['is_save'] = SaveExt::model()->count("pid=$id and uid=$uid")?1:0;
			// }
		}
		$data['tags'] = [];
		$data['tags'][] = ['name'=>'商家','value'=>$info->company];
		if($info->area || $info->street) {
			$area = AreaExt::model()->findByPk($info->area);
			$street = AreaExt::model()->findByPk($info->street);
			$cd = '';
			$area && $cd .= $area->name;
			$street && $cd .= $street->name;
			$data['tags'][] = ['name'=>'产地','value'=>$cd];
		}
		if(isset(ProductExt::$types[$info->type])) {
			$tags = ProductExt::$types[$info->type]['tags'];
			if($tags) {
				$tagName = TagExt::$xinfangCate['direct'];
				foreach ($tags as $key => $value) {
					if($info->$key) {
						$data['tags'][] = ['name'=>$tagName[$value],'value'=>TagExt::model()->findByPk($info->$key)->name];
					}
				}
			}
		}
		// if($confs = $info->data_conf) {
		// 	$fields = Yii::app()->file->getFields();
		// 	$confs = json_decode($confs,true);
		// 	$ids = $tagname = [];
		// 	foreach ($confs as $key => $value) {
		// 		$ids[] = $value;
		// 	}
		// 	$criteria = new CDbCriteria;
		// 	$criteria->select = 'id,name';
		// 	$criteria->addInCondition('id',$ids);

		// 	$tags = TagExt::model()->findAll($criteria);
		// 	if($tags) {
		// 		foreach ($tags as $key => $value) {
		// 			$tagname[$value['id']] = $value['name'];
		// 		}
		// 	}
		// 	// var_dump($tags[0]['attributes']);exit;
		// 	foreach ($confs as $key => $value) {
		// 		$data['params'][$fields[$key]] = $tagname[$value];
		// 	}
		// 	$data['created'] = date('Y-m-d',$data['created']);
		// 	$data['updated'] = date('Y-m-d',$data['updated']);
			
		// }
		$data['created'] = date('Y-m-d',$data['created']);
		$data['updated'] = date('Y-m-d',$data['updated']);
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

    public function actionAddSave($pid='',$uid='',$type='')
    {
        if($pid&&$uid) {
            $staff = UserExt::model()->findByPk($uid);
            if($save = SaveExt::model()->find('pid='.(int)$pid.' and type='.$type.' and uid='.$staff->id)) {
                SaveExt::model()->deleteAllByAttributes(['pid'=>$pid,'uid'=>$staff->id,'type'=>$type]);
                $this->frame['data'] = 0;
                $this->returnSuccess('取消收藏成功');
            } else {
                $save = new SaveExt;
                $save->uid = $staff->id;
                $save->pid = $pid;
                $save->type = $type;
                $save->save();
                if($type==1) {
                	$obj = new LogExt;
					$obj->pid = $pid;
					$obj->uid = $uid;
					$obj->type = 3;
					$obj->save();
                }
	                
                $this->frame['data'] = 1;
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

    public function actionGetTagArr()
    {
    	$aat = ProductExt::$types;
        $aats = [];
        $arr = [];
        foreach ($aat as $key => $value) {
            $aats[$value['name']] = $key;
        }
        $pyarr = ['yj'=>'i1','pq'=>'i2','hxt'=>'i3','jhq'=>'i4','jsq'=>'i5','jcsb'=>'i6','hc'=>'i7','cma'=>'i8','soft'=>'i9','jm'=>'i10','bx'=>'i11',];
    	$tags = TagExt::model()->normal()->findAll(['condition'=>"cate='tab'",'order'=>'sort asc']);
    	foreach ($tags as $key => $value) {
    		if(!isset($aats[$value->name]))
    			continue;
    		if(!isset($pyarr[$aats[$value->name]]))
    			continue;
    		$arr[] = [
    			'name'=>$value->name,'py'=>$aats[$value->name],'i'=>$pyarr[$aats[$value->name]]
    		];
    	}
    	// $arr = array_combine(array_values(CHtml::listData($tags,'id','name')),array_values(CHtml::listData($tags,'id','cate')));
    	$this->frame['data'] = $arr;
    }

    public function actionGetProTag($type='')
    {
    	$types = ProductExt::$types;
    	if(!isset($types[$type]))
    		return $this->returnError('参数错误');
    	$ty = $types[$type];
    	$data = $data['tags'] = [];
    	$data = [
    		'title'=>$ty['name'].'发布',
    	];
    	$tags = $ty['tags'];
    	foreach ($tags as $key => $value) {
    		$tmp = [
    			'name'=>TagExt::$xinfangCate['direct'][$value],
    			'field'=>$key,
    			'list'=>Yii::app()->db->createCommand("select id,name from tag where status=1 and cate='$value'")->queryAll(),
    		];
    		$data['tags'][] = $tmp;
    	}
    	$this->frame['data'] = $data;
    }

    public function actionAddPro()
    {
    	$arrs = Yii::app()->request->getPost('ProductExt',[]);
    	$user = UserExt::model()->findByPk($arrs['uid']);
    	if($user->status==0) {
            return $this->returnError('您的账号暂无权限操作，请联系管理员');
        }
    	$imgs = isset($arrs['images'])?$arrs['images']:[];
    	unset($arrs['images']);
    	if(isset($arrs['id'])&&$arrs['id']) {
    		if($title = $arrs['name']) {
    			if(Yii::app()->db->createCommand("select id from product where name='$title' and id!=".$arrs['id'])->queryScalar()){
    				return $this->returnError("已有该商品名，请勿重复");
    			}
    		}
    		$obj = ProductExt::model()->findByPk($arrs['id']);
    	} else {
    		if($title = $arrs['name']) 
    		if(Yii::app()->db->createCommand("select id from product where name='$title'")->queryScalar()){
    				return $this->returnError("已有该商品名，请勿重复");
    			}
    		$obj = new ProductExt;
    	}
    	$obj->attributes = $arrs;
    	
    	$obj->status = 0;
    	$obj->image = str_replace("https", "http", $obj->image);
    	if($obj->save()) {
    		Yii::app()->db->createCommand("delete from album where pid=".$obj->id." and type=1")->execute();
    		// AlbumExt::model()->deteleAllByAttributes(['pid'=>$arrs['id'],'type'=>1]);
    		if($imgs) {
    			if(!is_array($imgs)) {
    				if(strstr($imgs,',')) {
	    				$imgs = explode(',', $imgs);
	    			} else {
	    				$imgs = [$imgs];
	    			}
    			}
	    			
    			foreach ($imgs as $key => $value) {
    				$im = new AlbumExt;
    				$im->pid = $obj->id;
    				$im->url = str_replace("https", "http", $value);
    				$im->type = 1;
    				$im->save();
    			}
    		}
    	}
    	$this->returnSuccess('发布或编辑商品成功');
    }

    public function actionChangeStatus($id='',$status='')
    {
    	$obj = ProductExt::model()->findByPk($id);
    	$obj->status = $status;
    	$obj->save();
    }

    public function actionGetCompany($uid='')
    {
    	$obj = UserExt::model()->findByPk($uid);
    	$this->frame['data'] = $obj->company;
    }

}