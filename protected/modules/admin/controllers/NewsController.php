<?php
/**
 * 文章控制器
 * @author steven.allen <[<email address>]>
 * @date(2017.2.5)
 */
class NewsController extends AdminController{

	public $cates = [];

	/**
	 *相当于构造方法
	 */
	public function init()
	{
		parent::init();
		$this->cates = CHtml::listData(TagExt::model()->getTagByCate('wzlm')->normal()->findAll(),'id','name');
	}
	/**
	 * 文章列表
	 */
	public function actionList($type='title',$value='',$time_type='created',$time='',$cate='',$type='')
	{
		/**
		 * yii的db操作可以通过criteria类 用法超级简单
		 */
		$criteria = new CDbCriteria;
		$criteria->order = 'sort desc,updated desc';
		$criteria->addCondition('deleted=0');
		if($value = trim($value))
            if ($type=='title') {
                $criteria->addSearchCondition('title', $value);
            } 
        //添加时间、刷新时间筛选
        if($time_type!='' && $time!='')
        {
            list($beginTime, $endTime) = explode('-', $time);
            $beginTime = (int)strtotime(trim($beginTime));
            $endTime = (int)strtotime(trim($endTime));
            $criteria->addCondition("{$time_type}>=:beginTime");
            $criteria->addCondition("{$time_type}<:endTime");
            $criteria->params[':beginTime'] = TimeTools::getDayBeginTime($beginTime);
            $criteria->params[':endTime'] = TimeTools::getDayEndTime($endTime);

        }
		if($cate) {
			$criteria->addCondition('cid=:cid');
			$criteria->params[':cid'] = $cate;
		}
		if($type) {
			$criteria->addCondition('type=:type');
			$criteria->params[':type'] = $type;
		}
		//这个相当于M()->selecte当时封装了分页
		//其中 news->data是数据 news->pageination是分页
		$news = ArticleExt::model()->getList($criteria,20);
		// 这个是渲染页面
		$this->render('list',[
			'cates'=>$this->cates,
			'news'=>$news->data,
			'pager'=>$news->pagination,
			'type' => $type,
            'value' => $value,
            'time' => $time,
            'time_type' => $time_type,
            'cate'=>$cate,'type'=>$type]);
	}

	public function actionEdit($id = 0)
	{
		$info = $id ? ArticleExt::model()->findByPk($id) : new ArticleExt;
		if(Yii::app()->request->getIsPostRequest()) {
			$info->attributes = Yii::app()->request->getPost('ArticleExt',[]);

			if($info->save()) {
				$this->setMessage('操作成功','success',['list']);
			} else {
				$this->setMessage(array_values($info->errors)[0][0],'error');
			}
		} 
		$this->render('edit',['article'=>$info,'cates'=>$this->cates]);
	}

	public function actionAjaxSort($id=0,$sort=0)
	{
		if($id) {
			$model = ArticleExt::model()->findByPk($id);
			$model->sort = $sort;
			if($model->save()) {
				$this->setMessage('操作成功！','success');
				echo json_encode(['success'=>'1']);
			} else {
				echo json_encode(['success'=>'0']);
			}
		}
	}

	public function actionAjaxChangeStatus($id=0)
	{
		if($id) {
			$model = ArticleExt::model()->findByPk($id);
			$model->status = $model->status==1?0:1;
			if($model->save()) {
				$this->setMessage('操作成功！','success');
				echo json_encode(['success'=>'1']);
			} else {
				echo json_encode(['success'=>'0']);
			}
		}
	}

	public function actionAjaxDel($id=0)
	{
		if($id) {
			$model = ArticleExt::model()->findByPk($id);
			$model->deleted = 1;
			if($model->save()) {
				$this->setMessage('操作成功！','success');
				echo json_encode(['success'=>'1']);
			} else {
				echo json_encode(['success'=>'0']);
			}
		}
	}

	public function actionImagelist($hid)
	{
		// $_SERVER['HTTP_REFERER']='http://www.baidu.com';
		$house = ArticleExt::model()->findByPk($hid);
		if(!$house){
			$this->redirect('/admin');
		}
		if(Yii::app()->request->getIsPostRequest()) {
			AlbumExt::model()->deleteAllByAttributes(['pid'=>$house->id]);
			$values = Yii::app()->request->getPost("TkExt",[]);
			$urls = $values['album'];
			// $type = $values['type'];
			$sort = $values['sort'];
			if($urls) {
				foreach ($urls as $key => $value) {
					$model =  new AlbumExt;
					$model->pid = $house->id;
					$model->url = $value;
					$model->type = 2;
					$model->sort = $sort[$key];
					// $model->type = $type[$key];
					$model->save();
				}
			}
			$this->redirect('list');
		}
		$criteria = new CDbCriteria;
		$criteria->order = 'updated desc,id desc';
		$criteria->addCondition('pid=:hid');
		$criteria->params[':hid'] = $hid;
		$houses = AlbumExt::model()->getList($criteria,20);
		// var_dump($houses->dat);exit;
		// $this->render('imagelist',['infos'=>$houses->data,'pager'=>$houses->pagination,'house'=>$house]);
		$this->render('images',['infos'=>$houses->data,'pager'=>$houses->pagination,'house'=>$house]);
	}

	public function actionEditImage()
	{
		$id = Yii::app()->request->getQuery('id','');
		$hid = $_GET['hid'];
		$modelName = 'AlbumExt';
		$this->controllerName = '产品相册';
		$info = $id ? $modelName::model()->findByPk($id) : new $modelName;
		$info->getIsNewRecord() && $info->status = 1;
		if(Yii::app()->request->getIsPostRequest()) {
			$info->attributes = Yii::app()->request->getPost($modelName,[]);
			if($info->save()) {
				$this->setMessage('操作成功','success',['imagelist?hid='.$hid]);
			} else {
				$this->setMessage(array_values($info->errors)[0][0],'error');
			}
		} 
		$this->render('imageedit',['article'=>$info,'hid'=>$hid]);
	}
}