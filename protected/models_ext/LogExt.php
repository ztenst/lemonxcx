<?php 
/**
 * 球员类
 * @author steven.allen <[<email address>]>
 * @date(2017.2.12)
 */
class LogExt extends Log{
    public static $type = [
        1=>'浏览',2=>'咨询',3=>'收藏',4=>'分享'
    ];
	/**
     * 定义关系
     */
    public function relations()
    {
         return array(
            'user'=>array(self::BELONGS_TO, 'UserExt', 'uid'),
            'pro'=>array(self::BELONGS_TO, 'ProductExt', 'pid'),
        );
    }

    /**
     * @return array 验证规则
     */
    public function rules() {
        $rules = parent::rules();
        return array_merge($rules, array(
            // array('name', 'unique', 'message'=>'{attribute}已存在')
        ));
    }

    /**
     * 返回指定AR类的静态模型
     * @param string $className AR类的类名
     * @return CActiveRecord Admin静态模型
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function afterFind() {
        parent::afterFind();
        // if(!$this->image){
        //     $this->image = SiteExt::getAttr('qjpz','productNoPic');
        // }
    }

    public function beforeValidate() {
        if($this->uid&&!$this->name) {
            if($user = $this->user) {
                $this->name = $user->name;
                $this->phone = $user->phone;
            }
        }
        if($this->pid&&!$this->pname) {
            if($pro = $this->pro) {
                $this->pname = $pro->name;
            }
        }
        if($this->getIsNewRecord()) {

            // $res = Yii::app()->controller->sendNotice(($this->plot?$this->plot->title:'').'有新举报，举报原因为：'.$this->reason.'，请登陆后台审核','',1);
            
            $this->created = $this->updated = time();
        }
        else {
            // if($this->status==1&&Yii::app()->db->createCommand("select status from report where id=".$this->id)->queryScalar()==0) {
                
            // }
            $this->updated = time();
        }
        return parent::beforeValidate();
    }

    /**
     * 命名范围
     * @return array
     */
    public function scopes()
    {
        $alias = $this->getTableAlias();
        return array(
            'sorted' => array(
                'order' => "{$alias}.sort desc,{$alias}.updated desc",
            ),
            'normal' => array(
                'condition' => "{$alias}.status=1 and {$alias}.deleted=0",
                'order'=>"{$alias}.sort desc,{$alias}.updated desc",
            ),
            'undeleted' => array(
                'condition' => "{$alias}.deleted=0",
                // 'order'=>"{$alias}.sort desc,{$alias}.updated desc",
            ),
        );
    }

    /**
     * 绑定行为类
     */
    public function behaviors() {
        return array(
            'CacheBehavior' => array(
                'class' => 'application.behaviors.CacheBehavior',
                'cacheExp' => 0, //This is optional and the default is 0 (0 means never expire)
                'modelName' => __CLASS__, //This is optional as it will assume current model
            ),
            'BaseBehavior'=>'application.behaviors.BaseBehavior',
        );
    }

}