<?php 
/**
 * 相册类
 * @author steven.allen <[<email address>]>
 * @date(2017.2.5)
 */
class ProductExt extends Product{
	/**
     * 定义关系
     */
    public function relations()
    {
        return array(
            'images'=>array(self::HAS_MANY, 'AlbumExt', 'pid'),
            'user'=>array(self::BELONGS_TO, 'UserExt', 'uid'),
        );
    }
    public static $status = array(
        0 => '审核中',
        1 => '上架',
        2 => '下架',
    );
    /**
     * @var array 状态按钮样式
     */
    public static $statusStyle = array(
        0 => 'btn btn-sm btn-warning',
        1 => 'btn btn-sm btn-primary',
        2 => 'btn btn-sm btn-danger'
    );

    public static $types = [
        'yj'=>['name'=>'药剂','tags'=>['cid'=>'yjzl','mid'=>'yjcl','fid'=>'yjgn'],'isarea'=>1,'isprice'=>1,'filters'=>['origin'=>['area','yjzl','yjprice'],'more'=>['yjgn','yjcl','sort']]],
        'pq'=>['name'=>'喷枪','tags'=>['cid'=>'pqkj'],'isarea'=>1,'isprice'=>1,'filters'=>['origin'=>['area','pqkj','pqprice'],'more'=>['sort']]],
        'hxt'=>['name'=>'活性炭','tags'=>['cid'=>'hxtyl','fid'=>'hxtgn'],'isarea'=>0,'isprice'=>1,'filters'=>['origin'=>['hxtyl','hxtgn','hxtprice'],'more'=>['sort']]],
        'jhq'=>['name'=>'净化器','tags'=>['cid'=>'jhqjyzb','fid'=>'jhqgn','cadrid'=>'jhqcadr','ccmid'=>'jhqccm'],'isarea'=>1,'isprice'=>1,'filters'=>['origin'=>['area','jhqcadr','jhqprice'],'more'=>['jhqgn','jhqjyzb','jhqccm','sort']]],
        'jsq'=>['name'=>'净水器','tags'=>['cid'=>'jsqglcj','mid'=>'jsqglcz'],'isarea'=>1,'isprice'=>1,'filters'=>['origin'=>['area','jsqglcz','jsqprice'],'more'=>['jsqglcj','sort']]],
        'jcsb'=>['name'=>'检测设备','tags'=>['cid'=>'jcsblx','fid'=>'jcsbpp'],'isarea'=>0,'isprice'=>1,'filters'=>['origin'=>['jcsbpp','jcsblx','jcsbprice'],'more'=>['sort']]],
        'hc'=>['name'=>'耗材','tags'=>['cid'=>'hczl','fid'=>'hcpp','mid'=>'hccz'],'isarea'=>0,'isprice'=>1,'filters'=>['origin'=>['hczl','hcpp','hcprice'],'more'=>['hccz','sort']]],
        'jm'=>['name'=>'加盟','tags'=>['cid'=>'jmzl','fid'=>'jmpp'],'isarea'=>1,'isprice'=>0,'filters'=>['origin'=>['area','jmzl','jmpp'],'more'=>['sort']]],
        'cma'=>['name'=>'CMA合作','tags'=>['cid'=>'cmazl','fid'=>'cmajb'],'isarea'=>1,'isprice'=>0,'filters'=>['origin'=>['area','cmazl','cmajb'],'more'=>['sort']]],
        'soft'=>['name'=>'软件服务','tags'=>['cid'=>'gwzz'],'isarea'=>0,'isprice'=>1,'filters'=>['origin'=>[],'more'=>[]]]
    ];

    public static $tags = [
    'field0'=>'',
    'field1'=>'',
    'field2'=>'',
    'field3'=>'',
    'field4'=>'',
    'field5'=>'',
    'field6'=>'',
    ];

    public function __set($name='',$value='')
    {
        // var_dump($name);
       if (isset(self::$tags[$name])){
            if(is_array($this->data_conf))
                $data_conf = $this->data_conf;
            else
                $data_conf = CJSON::decode($this->data_conf);
            self::$tags[$name] = $value;
            $data_conf[$name] = $value;
            // var_dump(1);exit;
            $this->data_conf = json_encode($data_conf);
        }
        else
            parent::__set($name, $value);
    }

    public function __get($name='')
    {
        if (isset(self::$tags[$name])) {
            if(is_array($this->data_conf))
                $data_conf = $this->data_conf;
            else
                $data_conf = CJSON::decode($this->data_conf);

            if(!isset($data_conf[$name]))
                $value = self::$tags[$name];
            else
                $value = self::$tags[$name] ? self::$tags[$name] : $data_conf[$name];

            return $value;
        } else{
            return parent::__get($name);
        }
    }

    /**
     * @return array 验证规则
     */
    public function rules() {
        $rules = parent::rules();
        return array_merge($rules, array(
             array(implode(',',array_keys(self::$tags)), 'safe'),
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
        if(!$this->image) {
            $this->image = SiteExt::getAttr('qjpz','productnopic');
        }
    }

    public function beforeValidate() {
        if($this->getIsNewRecord()){
            if($this->status==0) {
                if($tel = SiteExt::getAttr('qjpz','notice'))
                    SmsExt::sendMsg('新增商品',$tel,['product'=>$this->name]);
            }
            $this->created = $this->updated = time();
        }
        else {
            if($this->status==0 && Yii::app()->db->createCommand("select status from product where id=".$this->id)->queryScalar()==1) {
                if($tel = SiteExt::getAttr('qjpz','notice'))
                    SmsExt::sendMsg('新增商品',$tel,['product'=>$this->name]);
            }
            if($this->status==1 && Yii::app()->db->createCommand("select status from product where id=".$this->id)->queryScalar()==0) {
                if($tel = $this->phone)
                    SmsExt::sendMsg('商品审核通过',$tel,['product'=>$this->name]);
            }
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

    public static function getObjFromCate($cid='',$limit='')
    {
        $criteria = new CDbCriteria;
        $criteria->addCondition('cid=:cid');
        $criteria->params[':cid'] = $cid;
        $criteria->limit = $limit;
        return ProductExt::model()->normal()->findAll($criteria);
    }
}