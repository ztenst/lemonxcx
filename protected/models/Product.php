<?php

/**
 * This is the model class for table "product".
 *
 * The followings are the available columns in table 'product':
 * @property integer $id
 * @property string $name
 * @property integer $uid
 * @property double $price
 * @property integer $is_rz
 * @property integer $hits
 * @property string $phone
 * @property string $company
 * @property double $old_price
 * @property string $type
 * @property integer $ccmid
 * @property integer $cadrid
 * @property integer $street
 * @property integer $area
 * @property integer $mid
 * @property integer $fid
 * @property integer $cid
 * @property string $image
 * @property string $shortdes
 * @property string $content
 * @property string $data_conf
 * @property integer $sort
 * @property integer $deleted
 * @property integer $status
 * @property integer $created
 * @property integer $updated
 */
class Product extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'product';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('company, created', 'required'),
			array('uid, is_rz, hits, ccmid, cadrid, street, area, mid, fid, cid, sort, deleted, status, created, updated', 'numerical', 'integerOnly'=>true),
			array('price, old_price', 'numerical'),
			array('name, type, image', 'length', 'max'=>255),
			array('phone', 'length', 'max'=>20),
			array('company, shortdes', 'length', 'max'=>100),
			array('content, data_conf', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, name, uid, price, is_rz, hits, phone, company, old_price, type, ccmid, cadrid, street, area, mid, fid, cid, image, shortdes, content, data_conf, sort, deleted, status, created, updated', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'uid' => 'Uid',
			'price' => 'Price',
			'is_rz' => 'Is Rz',
			'hits' => 'Hits',
			'phone' => 'Phone',
			'company' => 'Company',
			'old_price' => 'Old Price',
			'type' => 'Type',
			'ccmid' => 'Ccmid',
			'cadrid' => 'Cadrid',
			'street' => 'Street',
			'area' => 'Area',
			'mid' => 'Mid',
			'fid' => 'Fid',
			'cid' => 'Cid',
			'image' => 'Image',
			'shortdes' => 'Shortdes',
			'content' => 'Content',
			'data_conf' => 'Data Conf',
			'sort' => 'Sort',
			'deleted' => 'Deleted',
			'status' => 'Status',
			'created' => 'Created',
			'updated' => 'Updated',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('uid',$this->uid);
		$criteria->compare('price',$this->price);
		$criteria->compare('is_rz',$this->is_rz);
		$criteria->compare('hits',$this->hits);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('company',$this->company,true);
		$criteria->compare('old_price',$this->old_price);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('ccmid',$this->ccmid);
		$criteria->compare('cadrid',$this->cadrid);
		$criteria->compare('street',$this->street);
		$criteria->compare('area',$this->area);
		$criteria->compare('mid',$this->mid);
		$criteria->compare('fid',$this->fid);
		$criteria->compare('cid',$this->cid);
		$criteria->compare('image',$this->image,true);
		$criteria->compare('shortdes',$this->shortdes,true);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('data_conf',$this->data_conf,true);
		$criteria->compare('sort',$this->sort);
		$criteria->compare('deleted',$this->deleted);
		$criteria->compare('status',$this->status);
		$criteria->compare('created',$this->created);
		$criteria->compare('updated',$this->updated);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Product the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
