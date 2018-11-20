<?php
class CusController extends ApiController
{
	public function actionList()
	{
		$datas = $datas['list'] = [];
		$cid = (int)Yii::app()->request->getQuery('cid',0);
		$page = (int)Yii::app()->request->getQuery('page',1);
		$limit = (int)Yii::app()->request->getQuery('limit',20);
        $uid = (int)Yii::app()->request->getQuery('uid',0);
        $type = Yii::app()->request->getQuery('type','');
        $save = (int)Yii::app()->request->getQuery('save',0);
        $savetype = (int)Yii::app()->request->getQuery('savetype',0);
		$kw = $this->cleanXss(Yii::app()->request->getQuery('kw',''));
        $status = Yii::app()->request->getQuery('status',1);
		$criteria = new CDbCriteria;
		$criteria->order = 't.sort desc,t.updated desc';
		$criteria->limit = $limit;
        // 这段代码比较恶心 以后万一有bug再说
		// $criteria->addCondition('t.status=1');
		if($kw) {
			$criteria->addSearchCondition('t.title',$kw);
		}
		if($cid) {
			$criteria->addCondition("t.cid=:cid");
			$criteria->params[':cid'] = $cid;
		}
        if($uid&&!$save) {
            $criteria->addCondition("t.uid=:uid");
            $criteria->params[':uid'] = $uid;
        }
        if(is_numeric($type)) {
            $criteria->addCondition("type=:type");
            $criteria->params[':type'] = $type;
        }
        if($savetype&&$save&&$uid) {
            $ids = [];
            $saeids = Yii::app()->db->createCommand("select pid from save where uid=$uid and type=$savetype")->queryAll();
            if($saeids) {
                foreach ($saeids as $key => $value) {
                    $ids[] = $value['pid'];
                }
            }
            $criteria->addInCondition('t.id',$ids);
        }
        if(is_numeric($status)) {
            $criteria->addCondition('t.status='.$status);
        }
		$ress = ArticleExt::model()->getList($criteria);
		$infos = $ress->data;
		$pager = $ress->pagination;
		if($infos) {
			foreach ($infos as $key => $value) {
				$data['list'][] = [
					'id'=>$value->id,
					'name'=>Tools::u8_title_substr($value->title,20),
					// 'cate'=>$value->cate?$value->cate->name:'',
					'date'=>date('Y-m-d H:i',$value->updated),
					'author'=>$value->user?$value->user->name:'',
					'save_num'=>Yii::app()->db->createCommand("select count(id) from save where type=2 and pid=".$value->id)->queryScalar(),
					'praise_num'=>Yii::app()->db->createCommand("select count(id) from praise where cid=".$value->id)->queryScalar(),
                    'is_hot'=>$value->is_hot,
                    'hits'=>$value->hits,
                    'status'=>$value->status,
                    'status_word'=>ProductExt::$status[$value->status],
					// 'price'=>$value->price,
					// 'old_price'=>$value->old_price,
					// 'ts'=>$value->shortdes,
					'image'=>ImageTools::fixImage($value->image,700,360),
				];
			}
		}
		$data['num'] = $pager->itemCount;
		$data['page_count'] = $pager->pageCount;
		$data['page'] = $page;

		$this->frame['data'] = $data;
	}

    public function actionNewsTags()
    {
        $data = [];
        $tags = TagExt::model()->findAll("cate='wzbq'");
        if($tags) {
            foreach ($tags as $key => $value) {
                $data[] = ['id'=>$value->id,'name'=>$value->name];
            }
        }
        $this->frame['data'] = $data;
    }

    public function actionNewsTagsNews()
    {
        $data = [];
        $tags = TagExt::model()->findAll("cate='xwbq'");
        if($tags) {
            foreach ($tags as $key => $value) {
                $data[] = ['id'=>$value->id,'name'=>$value->name];
            }
        }
        $this->frame['data'] = $data;
    }

	public function actionInfo($id='',$uid='0')
	{
        $data = $data['comments'] = [];
		$info = ArticleExt::model()->findByPk($id);
        if(!$info) {
            return $this->returnError('帖子不存在');
        }
        $info->hits += 1;
        $info->save();
        $user = $info->user;
        $usernopic = SiteExt::getAttr('qjpz','usernopic');
        $images = $imgs = [];
        if($imgsarr = AlbumExt::model()->findAll("pid=$id and type=2")) {
            foreach ($imgsarr as $key => $value) {
                $images[] = ImageTools::fixImage($value->url,600,600);
                $imgs[] = $value->url;
            }
        }
        $data = [
            'id'=>$info->id,
            'title'=>$info->title,
            'author'=>$user?$user->name:'暂无',
            'image'=>$user&&$user->image?ImageTools::fixImage($user['image'],200,200):ImageTools::fixImage($usernopic,200,200),
            'time'=>date('Y-m-d H:i',$info['updated']),
            'content'=>$info->content,
            'hits'=>$info->hits,
            'imgs'=>$imgs,
            'cid'=>$info->cid,
            'images'=>$images,
            'is_save'=>SaveExt::model()->find("type=2 and uid=$uid and pid=$id")?1:0,
        ];
        if($comments = $info->comments) {
            foreach ($comments as $key => $value) {
                $user = $value->user;
                // $
                // if($value->)
                $tmp = [
                    'id'=>$value->id,
                    'image'=>$user->image?ImageTools::fixImage($user['image'],200,200):ImageTools::fixImage($usernopic,200,200),
                    'name'=>$user->name,
                    'content'=>$value->content,
                    'time'=>date('Y-m-d H:i',$value['updated']),
                    'praises'=>$value->praise,
                    'is_praised'=>!$uid?false:(Yii::app()->db->createCommand("select id from praise where uid=$uid and cid=".$value->id)->queryScalar()?true:false),
                ];
                $data['comments'][] = $tmp;
            }
        }
		// $data = $info->attributes;
        // if($info->user) {

        // }
		// $data['image'] = ImageTools::fixImage($data['image'],700,360);
		// $data['created'] = date('Y-m-d',$data['created']);
		// $data['updated'] = date('Y-m-d',$data['updated']);
		$this->frame['data'] = $data;
	}

	public function actionAddSave($pid='',$uid='',$type=1)
    {
        if($pid&&$uid) {
            $staff = UserExt::model()->findByPk($uid);
            if($save = SaveExt::model()->find('pid='.(int)$pid.' and type=2 and uid='.$staff->id)) {
                SaveExt::model()->deleteAllByAttributes(['pid'=>$pid,'uid'=>$staff->id,'type'=>2]);
                $this->frame['data'] = 0;
                $this->returnSuccess('取消收藏成功');
            } else {
                $save = new SaveExt;
                $save->uid = $staff->id;
                $save->pid = $pid;
                $save->type = $type;
                $save->save();
                $this->frame['data'] = 1;
                $this->returnSuccess('收藏成功');
            }
        }else {
            $this->returnError('请登录后操作');
        }
    }

    public function actionAddPraise($cid='',$uid='')
    {
        if($cid&&$uid) {
            $staff = UserExt::model()->findByPk($uid);
            if($save = PraiseExt::model()->find('cid='.(int)$cid.' and uid='.$staff->id)) {
                PraiseExt::model()->deleteAllByAttributes(['cid'=>$cid,'uid'=>$staff->id]);
                $this->returnSuccess('取消点赞成功');
                $this->frame['data'] = 0;
            } else {
                $save = new PraiseExt;
                $save->uid = $staff->id;
                $save->cid = $cid;
                // $save->type = 2;
                $save->save();

                $this->frame['data'] = 1;
                $this->returnSuccess('点赞成功');
            }
            if($ar = $save->comment) {
                $num = Yii::app()->db->createCommand("select count(id) from praise where cid=".$save->cid)->queryScalar();
                $ar->praise = $num;
                $ar->save();
            }
        }else {
            $this->returnError('请登录后操作');
        }
    }

    public function actionAddNews()
    {
    	if(Yii::app()->request->getIsPostRequest()) {
            $id = Yii::app()->request->getPost('id','');
    		$uid = Yii::app()->request->getPost('uid','');
    		$title = Yii::app()->request->getPost('title','');
    		$content = Yii::app()->request->getPost('content','');
    		$fm = Yii::app()->request->getPost('fm','');
    		$imgs = Yii::app()->request->getPost('imgs','');
            $cid = Yii::app()->request->getPost('cid','');
    		if(!$uid || !$title || !$content) {
    			return $this->returnError('参数错误');
    		}
    		$obj = $id?ArticleExt::model()->findByPk($id):new ArticleExt;
            if($obj->getIsNewRecord()&&ArticleExt::model()->find("title='$title'")) {
                return $this->returnError('帖子名已存在，请勿重复发布');
            }
    		$obj->status = 0;
    		$obj->uid = $uid;
    		$obj->title = $title;
    		$obj->content = $content;
    		$obj->image = $fm;
            $obj->cid = $cid;
            $obj->type = 2;
    		if($obj->save()) {
    			Yii::app()->db->createCommand("delete from album where pid=".$obj->id." and type=2")->execute();
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
                        $im->url = $value;
                        $im->type = 2;
                        $im->save();
                    }
                }
    		} else {
    			return $this->returnError(current(current($obj->getErrors())));
    		}


    	}
    }

    public function actionAddLog($uid='',$pid='',$type='')
    {
        $obj = new LogExt;
        $obj->uid = $uid;
        $obj->pid = $pid;
        $obj->type = $type;
        $pro = ProductExt::model()->findByPk($pid);
        $user = UserExt::model()->findByPk($uid);
        $phone = $pro->phone;
        $time = time()-3600;
        if($type==2) {
            // 每小时不超过3次
            $num = LogExt::model()->count("pid=$pid and uid=$uid and created>$time and type=2");
            if($num<3) {
                SmsExt::sendMsg('购买通知卖家',$phone,['user'=>$user->name.($user->phone?$user->phone:''),'pro'=>$pro->name]);
            }
        }
        $obj->save();
        $this->returnSuccess('操作成功');
    }
    public function actionLogList($uid='',$type='',$utype=1)
    {
        if($utype==1) {
            $logs = LogExt::model()->findAll(['condition'=>"puid=$uid",'order'=>'updated desc','limit'=>50]);
        } else {
            $logs = LogExt::model()->findAll(['condition'=>"uid=$uid",'order'=>'updated desc','limit'=>50]);
        }
        $data = [];
        if($logs) {
            foreach ($logs as $key => $value) {
                $data[] = [
                    'id'=>$value->id,
                    'name'=>$utype==1?$value->name:'我',
                    'words'=>LogExt::$type[$type].'了商品 '.$value->pname,
                    'time'=>date('Y-m-d H:i',$value->created),
                ];
            }
        }
        $this->frame['data'] = $data;
    }

    public function actionChangeStatus($id='',$status='')
    {
        $obj = ArticleExt::model()->findByPk($id);
        $obj->status = $status;
        $obj->save();
    }

    public function actionAddComment()
    {
        $values = Yii::app()->request->getPost('CommentExt',[]);
        $obj = new CommentExt;
        if($obj->getIsNewRecord()&&CommentExt::model()->find("content='".$obj->content."'")) {
                return $this->returnError('该评论已存在，请勿重复发布');
            }
        $obj->attributes = $values;
        if(!$obj->save()) {
            $this->returnError(current(current($obj->getErrors())));
        }
    }
}