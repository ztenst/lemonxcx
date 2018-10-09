<?php
$this->pageTitle = ProductExt::$types[$type]['name'].'新建/编辑';
$this->breadcrumbs = array('产品管理', $this->pageTitle);
?>
<?php $this->widget('ext.ueditor.UeditorWidget',array('id'=>'ArticleExt_content','options'=>"toolbars:[['fullscreen','source','undo','redo','|','customstyle','paragraph','fontfamily','fontsize'],
        ['bold','italic','underline','fontborder','strikethrough','superscript','subscript','removeformat',
        'formatmatch', 'autotypeset', 'blockquote', 'pasteplain','|',
        'forecolor','backcolor','insertorderedlist','insertunorderedlist','|',
        'rowspacingtop','rowspacingbottom', 'lineheight','|',
        'directionalityltr','directionalityrtl','indent','|'],
        ['justifyleft','justifycenter','justifyright','justifyjustify','|','link','unlink','|',
        'insertimage','emotion','scrawl','insertvideo','music','attachment','map',
        'insertcode','|',
        'horizontal','inserttable','|',
        'print','preview','searchreplace']]")); ?>

<?php $form = $this->beginWidget('HouseForm', array('htmlOptions' => array('class' => 'form-horizontal'))) ?>
<div class="form-group">
    <label class="col-md-2 control-label">名称<span class="required" aria-required="true">*</span></label>
    <div class="col-md-4">
        <?php echo $form->textField($article, 'name', array('class' => 'form-control')); ?>
                <input type="hidden" name="ProductExt[type]" value="<?=$type?>">

    </div>
    <div class="col-md-2"><?php echo $form->error($article, 'name') ?></div>
</div>
<?php if($type && ProductExt::$types[$type]['isarea']==1): ?>
    <?php 
    $parentArea = AreaExt::model()->parent()->normal()->findAll();
$parent = $article->area?$article->area:(isset($parentArea[0])?$parentArea[0]->id:0);
$childArea = $parent ? AreaExt::model()->getByParent($parent)->normal()->findAll() : array(0=>'--无子分类--'); ?>
    <div class="form-group">
    <label class="col-md-2 control-label text-nowrap">所在区域<span class="required" aria-required="true">*</span></label>
    <div class="col-md-10">
        <?php
        echo $form->dropDownList($article , 'area' ,CHtml::listData($parentArea,'id','name') , array(
                'class'=>'form-control input-inline',
                'ajax' =>array(
                    'url' => Yii::app()->createUrl('admin/area/ajaxGetArea'),
                    'update' => '#CompanyExt_street',
                    'data'=>array('area'=>'js:this.value'),
                )
            )
        );
        ?>
        <?php
        echo $form->dropDownList($article , 'street' ,$childArea ? CHtml::listData($childArea,'id','name'):array(0=>'--无子分类--') , array('class'=>'form-control input-inline'));
        ?>
        <span class="help-block"><?php echo $form->error($article, 'area').$form->error($article, 'street'); ?></span>
    </div>
</div>
    <?php endif;?>
<?php if($type && $tags = ProductExt::$types[$type]['tags']) :?>
    <?php foreach ($tags as $key => $value) { ?>
       <div class="form-group">
            <label class="col-md-2 control-label"><?=TagExt::$xinfangCate['direct'][$value]?></label>
            <div class="col-md-4">
                <?php echo $form->dropDownList($article, $key, CHtml::listData(TagExt::model()->findAll("cate='$value'"),'id','name'), array('class' => 'form-control', 'encode' => false)); ?>
            </div>
            <div class="col-md-2"><?php echo $form->error($article, $key) ?></div>
        </div>
    <?php } ?>
    <?php endif;?>

<div class="form-group">
    <label class="col-md-2 control-label">点击量<span class="required" aria-required="true">*</span></label>
    <div class="col-md-4">
        <?php echo $form->textField($article, 'hits', array('class' => 'form-control')); ?>
    </div>
    <div class="col-md-2"><?php echo $form->error($article, 'hits') ?></div>
</div>
<div class="form-group">
    <label class="col-md-2 control-label">现价</label>
    <div class="col-md-4">
        <?php echo $form->textField($article, 'price',array('class' => 'form-control')); ?>
    </div>
    <div class="col-md-2"><?php echo $form->error($article, 'price') ?></div>
</div>
<div class="form-group">
    <label class="col-md-2 control-label">原价</label>
    <div class="col-md-4">
        <?php echo $form->textField($article, 'old_price', array('class' => 'form-control')); ?>
    </div>
    <div class="col-md-2"><?php echo $form->error($article, 'old_price') ?></div>
</div>
<div class="form-group">
    <label class="col-md-2 control-label">一句话简介<span class="required" aria-required="true">*</span></label>
    <div class="col-md-4">
        <?php echo $form->textField($article, 'shortdes', array('class' => 'form-control')); ?>
    </div>
    <div class="col-md-2"><?php echo $form->error($article, 'shortdes') ?></div>
</div>
<div class="form-group">
    <label class="col-md-2 control-label">公司名<span class="required" aria-required="true">*</span></label>
    <div class="col-md-4">
        <?php echo $form->textField($article, 'company', array('class' => 'form-control')); ?>
    </div>
    <div class="col-md-2"><?php echo $form->error($article, 'company') ?></div>
</div>
<div class="form-group">
    <label class="col-md-2 control-label">联系电话<span class="required" aria-required="true">*</span></label>
    <div class="col-md-4">
        <?php echo $form->textField($article, 'phone', array('class' => 'form-control')); ?>
    </div>
    <div class="col-md-2"><?php echo $form->error($article, 'phone') ?></div>
</div>
<div class="form-group">
    <label class="col-md-2 control-label">产品特点</label>
    <div class="col-md-8">
        <?php echo $form->textArea($article, 'content', array('id'=>'ArticleExt_content')); ?>
    </div>
    <div class="col-md-2"><?php echo $form->error($article, 'content')  ?></div>
</div>
<div class="form-group">
    <label class="col-md-2 control-label">是否认证</label>
    <div class="col-md-4">
        <?php echo $form->radioButtonList($article, 'is_rz', ['否','是'], array('separator' => '')); ?>
    </div>
    <div class="col-md-2"><?php echo $form->error($article, 'is_rz') ?></div>
</div>
<div class="form-group">
    <label class="col-md-2 control-label text-nowrap">封面图</label>
    <div class="col-md-8">
        <?php $this->widget('FileUpload',array('model'=>$article,'attribute'=>'image','inputName'=>'img','width'=>400,'height'=>300)); ?>
        <span class="help-block">建议尺寸：600*400</span> 
    </div>
</div>

<div class="form-actions">
    <div class="row">
        <div class="col-md-offset-3 col-md-9">
            <button type="submit" class="btn green">保存</button>
            <?php echo CHtml::link('返回',$this->createUrl('list'), array('class' => 'btn default')) ?>
        </div>
    </div>
</div>

<?php $this->endWidget() ?>

<?php
// Yii::app()->clientScript->registerScriptFile('/static/admin/pages/scripts/esf-add-images.js', CClientScript::POS_END);

$js = "

    var getHousesAjax =
     {
        url: '".$this->createUrl('/admin/plot/AjaxGetHouse')."',"."
        dataType: 'json',
        delay: 250,
        data: function (params) {
            return {
                kw:params
            };
        },
        results:function(data){
            var items = [];

             $.each(data.results,function(){
                var tmp = {
                    id : this.id,
                    text : this.name
                }
                items.push(tmp);
            });

            return {
                results: items
            };
        },
        processResults: function (data, page) {
            var items = [];
             $.each(data.msg,function(){
                var tmp = {
                    id : this.id,
                    text : this.title
                }
                items.push(tmp);
            });
            return {
                results: items
            };
        }
    }
        $(function(){

           $('.select2').select2({
              placeholder: '请选择',
              allowClear: true
           });

				var houses_edit = $('#plot');
				var data = {};
				if( houses_edit.length && houses_edit.data('houses') ){
					data = eval(houses_edit.data('houses'));
				}

				$('#plot').select2({
					multiple:true,
					ajax: getHousesAjax,
					language: 'zh-CN',
					initSelection: function(element, callback){
						callback(data);
					}
				});

             $('.form_datetime').datetimepicker({
                 autoclose: true,
                 isRTL: Metronic.isRTL(),
                 format: 'yyyy-mm-dd hh:ii',
                 // minView: 'm',
                 language: 'zh-CN',
                 pickerPosition: (Metronic.isRTL() ? 'bottom-right' : 'bottom-left'),
             });

             $('.form_datetime1').datetimepicker({
                 autoclose: true,
                 isRTL: Metronic.isRTL(),
                 format: 'yyyy-mm-dd',
                 minView: 'month',
                 language: 'zh-CN',
                 pickerPosition: (Metronic.isRTL() ? 'bottom-right' : 'bottom-left'),
             });
        });
        ";


Yii::app()->clientScript->registerScript('add',$js,CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile('/static/global/plugins/select2/select2.min.js', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile('/static/global/plugins/select2/select2_locale_zh-CN.js', CClientScript::POS_END);
Yii::app()->clientScript->registerCssFile('/static/global/plugins/select2/select2.css');
Yii::app()->clientScript->registerCssFile('/static/admin/pages/css/select2_custom.css');

Yii::app()->clientScript->registerScriptFile('/static/admin/pages/scripts/addCustomizeDialog.js', CClientScript::POS_END);
Yii::app()->clientScript->registerCssFile('/static/global/plugins/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css');
Yii::app()->clientScript->registerScriptFile('/static/global/plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js', CClientScript::POS_END);
Yii::app()->clientScript->registerScriptFile('/static/global/plugins/bootstrap-datetimepicker/js/locales/bootstrap-datetimepicker.zh-CN.js', CClientScript::POS_END, array('charset'=> 'utf-8'));
Yii::app()->clientScript->registerScriptFile('/static/global/plugins/bootbox/bootbox.min.js', CClientScript::POS_END);
?>
