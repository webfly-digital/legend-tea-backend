<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/'.CDeliverySDEK::$MODULE_ID.'/jsloader.php');
global $APPLICATION;

$pathToYmaps = Ipolh\SDEK\pvzWidjetHandler::getMapsScript();
if ($arParams['NOMAPS'] != 'Y')
    $APPLICATION->AddHeadString('<script data-id="'.CDeliverySDEK::$MODULE_ID.'" src="'.$pathToYmaps.'" type="text/javascript"></script>');
$APPLICATION->AddHeadString('<link href="/bitrix/js/'.CDeliverySDEK::$MODULE_ID.'/jquery.jscrollpane.css" type="text/css"  rel="stylesheet" />');

/* Used in backend AJAX calls */
$orderGoodsObject = CUtil::PhpToJSObject(CDeliverySDEK::setOrderGoods());

$objProfiles = array();
$arModes = array( /* Profiler */
    'PVZ' => array(
        'forced' => \Ipolh\SDEK\option::get('pvzID'),
        'profs'  => CDeliverySDEK::getDeliveryId('pickup')
    ),
    'POSTAMAT' => array(
        'forced' => COption::GetOptionString(CDeliverySDEK::$MODULE_ID,'pickupID',false),
        'profs'  => CDeliverySDEK::getDeliveryId('postamat')
    )
);

foreach($arModes as $mode => $content){
    $objProfiles[$mode] = array();
    if($content['forced']){
        foreach($content['profs'] as $id){
            $objProfiles[$mode][$id] = array(
                'tag'     => false,
                'price'   => false,
                'self'    => $content['forced'],
                'link'    => $id
            );
        }
    } else
        foreach($content['profs'] as $id)
            $objProfiles[$mode][$id] = array(
                'tag'   => false,
                'price' => false,
                'self'  => false,
                'link'  => $id
            );
}

$linkNamePVZ = \Ipolh\SDEK\option::get('buttonName'); /* Profiler */
if(!$linkNamePVZ) $linkNamePVZ = GetMessage("IPOLSDEK_FRNT_CHOOSEPICKUP");

$linkNamePOSTAMAT = COption::GetOptionString(CDeliverySDEK::$MODULE_ID,'buttonNamePST','');
if(!$linkNamePOSTAMAT) $linkNamePOSTAMAT = GetMessage("IPOLSDEK_FRNT_CHOOSEPOSTAMAT");
?>
<script>
    var IPOLSDEK_pvz = {
        label     : 'ISDEK_widjet',

        /* html of choosePvz-button */
        buttonPVZ : '<a href="javascript:void(0);" class="SDEK_selectPVZ" onclick="IPOLSDEK_pvz.selectPVZ(\'#id#\',\'PVZ\'); return false;"><?=$linkNamePVZ?></a>', /* Profiler */

        buttonPOSTAMAT: '<a href="javascript:void(0);" class="SDEK_selectPVZ" onclick="IPOLSDEK_pvz.selectPVZ(\'#id#\',\'POSTAMAT\'); return false;"><?=$linkNamePOSTAMAT?></a>',/* html of "select PVZ" button. */

        /* if opened */
        isActive: false,

        logging: <?=(\Ipolh\SDEK\option::get('debug_widget') == 'Y' && \Ipolh\SDEK\option::get('debugMode') == 'Y') ? 'true' : 'false'?>,

        /* which delivery is currently used */
        curDelivery : '<?=CDeliverySDEK::$selDeliv?>',

        /* which profile is currently counted */
        curProfile: false,

        /* if we need to re-init maps when ready */
        reinitMaps : false,

        /* which pvz-type is used */
        curMode: false,

        deliveries: <?=CUtil::PhpToJSObject($objProfiles)?>,

        city: '<?=CDeliverySDEK::$city?>',

        cityID: '<?=CDeliverySDEK::$cityId?>',
        sdekID: '<?=CDeliverySDEK::$sdekCity?>',

        cityCountry: <?=CUtil::PhpToJSObject($arResult['Subjects'])?>,

        payer: false,

        paysystem: false,

        /* where do we load adress of chosen PVZ */
        pvzInputs: [<?=substr($arResult['propAddr'],0,-1)?>],

        pickFirst: function(where){
            if(typeof(where) !== 'object')
                return false;
            for(var i in where)
                return i;
        },

        oldTemplate: false,

        ready: false,

        makeHTMLId: function(id){
            return 'ID_DELIVERY_' + ((id == 'sdek_pickup' || id == 'sdek_postamat' ) ?  id : 'ID_'+id); /* Profiler */
        },

        checkCheckedDel: function(delId,delivery){
            for(var i in delivery)
                if(delivery[i].CHECKED === 'Y'){
                    return (delivery[i].ID == delId);
                }
            return false;
        },

        guessCheckedDel: function(delId){
            return ('ID_DELIVERY_ID_'+delId == $('[name="DELIVERY_ID"]:checked').attr('ID'));
        },

        PVZ: {}<?php /*=CUtil::PhpToJSObject($arResult['PVZ'])*/?>, /* Profiler */

        POSTAMAT: {} <?php /*=CUtil::PhpToJSObject($arResult['POSTAMAT'])*/?>,

        /* object with PVZ of the city + coordinates for yandex */
        cityPVZ: {},

        /* scroll for PVZ-puncts */
        scrollPVZ: false,

        /* scroll for detail information */
        scrollDetail: false,

        /* false, if several PVZ in cities, or its Id */
        multiPVZ: false,

        init: function(){
            if(!IPOLSDEK_pvz.isFull(IPOLSDEK_pvz.deliveries.PVZ)) /* Profiler */
                console.warn(IPOLSDEK_pvz.label + ' warn: no delivery for PVZ');
            if(!IPOLSDEK_pvz.isFull(IPOLSDEK_pvz.deliveries.POSTAMAT))
                console.warn('SDEK vidjet warn: no delivery for postamats');

            IPOLSDEK_pvz.oldTemplate = $('#ORDER_FORM').length;

            /* ==== subscribe for from reloading */
            if(typeof BX !== 'undefined' && BX.addCustomEvent)
                BX.addCustomEvent('onAjaxSuccess', IPOLSDEK_pvz.onLoad);

            /* old js-core */
            /* rewriting ajax-success js function */
            if (window.jsAjaxUtil){
                jsAjaxUtil._CloseLocalWaitWindow = jsAjaxUtil.CloseLocalWaitWindow;
                jsAjaxUtil.CloseLocalWaitWindow = function (TID, cont){
                    jsAjaxUtil._CloseLocalWaitWindow(TID, cont);
                    IPOLSDEK_pvz.onLoad();
                }
            }
            /* == END */

            $(window).resize(IPOLSDEK_pvz.positWindow);

            IPOLSDEK_pvz.onLoad();

            /* html of the mask */
            $('body').append("<div id='SDEK_mask'></div>");

            /* Preloader */
            var preloaderHTML = '<div id="SDEK_preloader"><div class="SDEK-widget__preloader">';
            preloaderHTML += '<div class="SDEK-widget__preloader-truck">';
            preloaderHTML += '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 440.34302 315.71001"><g>';
            preloaderHTML += '<path class="path1" d="M416.43,188.455q-1.014-1.314-1.95-2.542c-7.762-10.228-14.037-21.74-19.573-31.897-5.428-9.959-10.555-19.366-16.153-25.871-12.489-14.513-24.24-21.567-35.925-21.567H285.128c-0.055.001-5.567,0.068-12.201,0.068h-9.409a14.72864,14.72864,0,0,0-14.262,11.104l-0.078.305V245.456l0.014,0.262a4.86644,4.86644,0,0,1-1.289,3.472c-1.587,1.734-4.634,2.65-8.812,2.65H14.345C6.435,251.839,0,257.893,0,265.334v46.388c0,7.441,6.435,13.495,14.345,13.495h49.36a57.8909,57.8909,0,0,0,115.335,0h82.61a57.89089,57.89089,0,0,0,115.335,0H414.53a25.8416,25.8416,0,0,0,25.813-25.811v-44.29C440.344,219.47,425.953,200.805,416.43,188.455ZM340.907,320.132a21.5865,21.5865,0,1,1-21.59-21.584A21.61074,21.61074,0,0,1,340.907,320.132ZM390.551,207.76c-0.451.745-1.739,1.066-3.695,0.941l-99.197-.005V127.782h42.886c11.539,0,19.716,5.023,28.224,17.337,5.658,8.19,20.639,33.977,21.403,35.293,0.532,1.027,1.079,2.071,1.631,3.125C386.125,191.798,392.658,204.279,390.551,207.76ZM121.372,298.548a21.58351,21.58351,0,1,1-21.583,21.584A21.6116,21.6116,0,0,1,121.372,298.548Z" transform="translate(0 -62.31697)"/>';
            preloaderHTML += '<path class="path2" d="M30.234,231.317h68a12.51354,12.51354,0,0,0,12.5-12.5v-50a12.51354,12.51354,0,0,0-12.5-12.5h-68a12.51354,12.51354,0,0,0-12.5,12.5v50A12.51418,12.51418,0,0,0,30.234,231.317Z" transform="translate(0 -62.31697)"/>';
            preloaderHTML += '<path class="path3" d="M143.234,231.317h68a12.51354,12.51354,0,0,0,12.5-12.5v-50a12.51354,12.51354,0,0,0-12.5-12.5h-68a12.51354,12.51354,0,0,0-12.5,12.5v50A12.51418,12.51418,0,0,0,143.234,231.317Z" transform="translate(0 -62.31697)"/>';
            preloaderHTML += '<path class="path4" d="M30.234,137.317h68a12.51354,12.51354,0,0,0,12.5-12.5v-50a12.51355,12.51355,0,0,0-12.5-12.5h-68a12.51355,12.51355,0,0,0-12.5,12.5v50A12.51418,12.51418,0,0,0,30.234,137.317Z" transform="translate(0 -62.31697)"/>';
            preloaderHTML += '<path class="path5" d="M143.234,137.317h68a12.51354,12.51354,0,0,0,12.5-12.5v-50a12.51354,12.51354,0,0,0-12.5-12.5h-68a12.51354,12.51354,0,0,0-12.5,12.5v50A12.51418,12.51418,0,0,0,143.234,137.317Z" transform="translate(0 -62.31697)"/>';
            preloaderHTML += '</g></svg>';
            preloaderHTML += '<div class="SDEK-widget__preloader-truck__grass"></div><div class="SDEK-widget__preloader-truck__road"></div>';
            preloaderHTML += '</div></div></div>';
            $('body').append(preloaderHTML);
        },

        getPrices: function(){
            var request = {
                CITY_TO    : IPOLSDEK_pvz.city,
                WEIGHT     : '<?=CDeliverySDEK::$orderWeight?>',
                PRICE      : '<?=CDeliverySDEK::$orderPrice?>',
                CITY_TO_ID : IPOLSDEK_pvz.cityID,
                CURPROF    : IPOLSDEK_pvz.curProfile,
                DELIVERY   : IPOLSDEK_pvz.curDelivery,
                GOODS      : <?=$orderGoodsObject?>,
                PERSON_TYPE_ID : IPOLSDEK_pvz.payer,
                PAY_SYSTEM_ID  : IPOLSDEK_pvz.paysystem
            };

            if(IPOLSDEK_pvz.logging){
                console.log(IPOLSDEK_pvz.label + ': requesting prices',request);
            }

            request['isdek_action'] = 'countDelivery';
            request['isdek_token']  = '<?=sdekHelper::getWidgetToken()?>';

            $.ajax({
                url: '/bitrix/js/ipol.sdek/ajax.php',
                type: 'POST',
                dataType: 'JSON',
                data: request,
                success: function(data){
                    if(IPOLSDEK_pvz.logging){
                        console.log(IPOLSDEK_pvz.label + ': response prices',data);
                    }
                    var links = {pickup:'PVZ',postamat:'POSTAMAT'}; /* Profiler */
                    for(var i in links){
                        var det = (i==='pickup') ? 'p' : 'i';
                        if(data[i] !== 'no'){
                            if(typeof data[det+"_date"] === 'undefined') transDate = data.date;
                            else transDate = data[det+"_date"];
                            $('#SDEK_'+det+'Price').html(data[i]);
                            $('#SDEK_'+det+'Date').html(transDate+"<?=GetMessage("IPOLSDEK_DAY")?>");
                        }else{
                            $('#SDEK_'+det+'Price').html("");
                            $('#SDEK_'+det+'Date').html("<?=GetMessage("IPOLSDEK_NO_DELIV")?>");
                        }
                    }
                }
            });
        },

        onLoad: function(ajaxAns){
            console.log('onLoad');
            /* place, where button "choose pvz" will be */
            var tag = false;

            IPOLSDEK_pvz.ready = false;

            var newTemplateAjax = (typeof(ajaxAns) !== 'undefined' && ajaxAns !== null && typeof(ajaxAns.sdek) === 'object');

            var cityUpdated = true;
            if($('#sdek_city').length>0){/* updating city */
                IPOLSDEK_pvz.city       = $('#sdek_city').val();
                IPOLSDEK_pvz.cityID     = $('#sdek_cityID').val();
                IPOLSDEK_pvz.sdekID     = $('#sdek_sdekID').val();
                IPOLSDEK_pvz.payer      = $('#sdek_payer').val();
                IPOLSDEK_pvz.paysystem  = $('#sdek_paysystem').val();
            }else{
                if(newTemplateAjax && typeof(ajaxAns.sdek) !== 'undefined'){
                    IPOLSDEK_pvz.city       = ajaxAns.sdek.city;
                    IPOLSDEK_pvz.cityID     = ajaxAns.sdek.cityId;
                    IPOLSDEK_pvz.sdekID     = ajaxAns.sdek.sdekId;
                    IPOLSDEK_pvz.payer      = ajaxAns.sdek.payer;
                    IPOLSDEK_pvz.paysystem  = ajaxAns.sdek.paysystem;
                    console.log('rewriten sdek id',IPOLSDEK_pvz.sdekID);
                }else
                    cityUpdated = false;
            }
            var checkPrices = true;

            for(var i in IPOLSDEK_pvz.deliveries){
                for(var j in IPOLSDEK_pvz.deliveries[i]){
                    tag = false;
                    if(IPOLSDEK_pvz.deliveries[i][j].self){
                        tag = $('#'+IPOLSDEK_pvz.deliveries[i][j].self);
                    }
                    else{
                        if(IPOLSDEK_pvz.oldTemplate){
                            var parentNd=$('#'+IPOLSDEK_pvz.makeHTMLId(j));
                            if(!parentNd.length) continue;
                            if(parentNd.closest('td', '#ORDER_FORM').length>0)
                                tag = parentNd.closest('td', '#ORDER_FORM').siblings('td:last');
                            else
                                tag = parentNd.siblings('label').find('.bx_result_price');
                        }
                        else
                        if(
                            (arguments.length > 0 && typeof(ajaxAns.order) !== 'undefined' && IPOLSDEK_pvz.checkCheckedDel(j,ajaxAns.order.DELIVERY))
                            ||
                            (arguments.length === 0 && IPOLSDEK_pvz.guessCheckedDel(j))
                        ){
                            if(!$('#IPOLSDEK_injectHere').length)
                                $('#bx-soa-delivery').find('.bx-soa-pp-company-desc').after('<div id="IPOLSDEK_injectHere"></div>');
                            if($('#IPOLSDEK_injectHere').length == 0){
                                IPOLSDEK_pvz.newTemplateLoader.listner();
                                checkPrices = false;
                            }else
                                tag = $('#IPOLSDEK_injectHere');
                        }
                    }

                    if(
                        tag.length > 0 &&
                        (
                            !tag.find('.SDEK_selectPVZ').length &&
                            (
                                !IPOLSDEK_pvz.deliveries[i][j].self ||
                                $('#'+IPOLSDEK_pvz.makeHTMLId(j)).length
                            )
                        )
                    ){
                        IPOLSDEK_pvz.deliveries[i][j].price = (tag.html()) ? tag.html() : false;
                        IPOLSDEK_pvz.deliveries[i][j].tag = tag;
                        IPOLSDEK_pvz.labelPzv(j, i);
                    }
                }
            }

            if(!cityUpdated) {
                /* if we don't have cdek_city - we load first time, so lets get PVZ adress from property and set it */
                IPOLSDEK_pvz.loadProfile();
            }

            /* which delivery is chosen */
            var sdekChecker = false;
            if($('#sdek_dostav').length>0){
                sdekChecker = $('#sdek_dostav').val();
                sdekChecker = (sdekChecker.indexOf(':') !== -1) ? sdekChecker.replace(":","_") : sdekChecker;
            }else
            if(newTemplateAjax)
                sdekChecker = ajaxAns.sdek.dostav;

            /* TODO curMode checked only after click on "selectPVZ" - may be some troubles with multi-templates */
            if(sdekChecker && newTemplateAjax){
                for(var i in IPOLSDEK_pvz.deliveries) {
                    for (var j in IPOLSDEK_pvz.deliveries[i]) {
                        if(j == sdekChecker){
                            IPOLSDEK_pvz.curMode = i;
                            break;
                        }
                    }
                }
            }

            /* choded PVZ - make choose it after load */
            if(sdekChecker && IPOLSDEK_pvz.curMode && IPOLSDEK_pvz.pvzId && IPOLSDEK_pvz.checkRightDelivery(sdekChecker)) {
                IPOLSDEK_pvz.choozePVZ(IPOLSDEK_pvz.pvzId, true);
            }

            if(sdekChecker)
                IPOLSDEK_pvz.curDelivery = sdekChecker;

            if(checkPrices)
                IPOLSDEK_pvz.getPrices();
        },

        newTemplateLoader: {
            timer   : false,
            listner : function (){
                if(IPOLSDEK_pvz.newTemplateLoader.timer){
                    clearTimeout(IPOLSDEK_pvz.newTemplateLoader.timer);
                    IPOLSDEK_pvz.newTemplateLoader.timer = false;
                    IPOLSDEK_pvz.onLoad();
                }else{
                    IPOLSDEK_pvz.newTemplateLoader.timer = setTimeout(IPOLSDEK_pvz.newTemplateLoader.listner, 1000);
                }
            }
        },

        checkRightDelivery: function(curSelected){
            if(typeof(IPOLSDEK_pvz.deliveries[IPOLSDEK_pvz.curMode]) === 'undefined')
                return false;
            if(typeof(IPOLSDEK_pvz.deliveries[IPOLSDEK_pvz.curMode][curSelected]) !== 'undefined')
                return true;
            for(var i in IPOLSDEK_pvz.deliveries[IPOLSDEK_pvz.curMode])
                if(typeof(IPOLSDEK_pvz.deliveries[IPOLSDEK_pvz.curMode][i].link !== 'undefined') && IPOLSDEK_pvz.deliveries[IPOLSDEK_pvz.curMode][i].link == curSelected)
                    return true;
            return false;
        },

        /* getting existed point via pointId because of different modes (will be erased) */
        checkPoint : function (pointId,mode){
            var point = false;

            if(typeof(mode) === 'undefined' || !mode){
                mode = IPOLSDEK_pvz.curMode;
            }

            if(pointId) {
                if (typeof (IPOLSDEK_pvz[mode][IPOLSDEK_pvz.city]) !== 'undefined') {
                    if(typeof (IPOLSDEK_pvz[mode][IPOLSDEK_pvz.city][[pointId]]) !== 'undefined'){
                        point = IPOLSDEK_pvz[mode][IPOLSDEK_pvz.city][[pointId]];
                    }
                } else if (typeof (IPOLSDEK_pvz[mode][IPOLSDEK_pvz.sdekID]) !== 'undefined') {
                    if(typeof (IPOLSDEK_pvz[mode][IPOLSDEK_pvz.sdekID][[pointId]]) !== 'undefined'){
                        point = IPOLSDEK_pvz[mode][IPOLSDEK_pvz.sdekID][[pointId]];
                    }
                }
            }

            return point;
        },

        /* addind link for chosing and sigh with info */
        labelPzv: function(i,mode){
            if(typeof(IPOLSDEK_pvz.deliveries[mode][i]) === 'undefined')
                return false;
            var tmpHTML = "<div class='sdek_pvzLair'>"+IPOLSDEK_pvz['button'+mode].replace('#id#',i) + "<br>";
            var point = IPOLSDEK_pvz.checkPoint(IPOLSDEK_pvz.pvzId,mode);
            if(point)
                tmpHTML += "<span class='sdek_pvzAddr'>" + point.Address+"</span><br>";
            if(IPOLSDEK_pvz.deliveries[mode][i].price)
                tmpHTML += IPOLSDEK_pvz.deliveries[mode][i].price;
            tmpHTML += "</div>";
            IPOLSDEK_pvz.deliveries[mode][i].tag.html(tmpHTML);
            if(!IPOLSDEK_pvz.oldTemplate)
                $('.sdek_pvzLair .SDEK_selectPVZ').addClass('btn btn-default btn-primary');
        },

        /* loading pvz from profile */
        profileAsked : false, /* for not-spamming */
        loadProfile:function(){
            var chznPnkt=false;
            for(var i in IPOLSDEK_pvz.pvzInputs){
                if(typeof(IPOLSDEK_pvz.pvzInputs[i]) === 'function') continue;
                chznPnkt = $('[name="ORDER_PROP_'+IPOLSDEK_pvz.pvzInputs[i]+'"]');
                if(chznPnkt.length>0)
                    break;
            }
            if(!chznPnkt || chznPnkt.length === 0) return;

            var seltdPVZ = chznPnkt.val();
            if(seltdPVZ.indexOf('#S') === -1) return;

            seltdPVZ=seltdPVZ.substr(seltdPVZ.indexOf('#S')+2);

            if(seltdPVZ <= 0)
                return false;
            else{
                var checks = ['PVZ','POSTAMAT']; /* Profiler */
                var pret = false;
                var point = false;
                for(var i in checks){
                    if(typeof(checks[i]) === 'function') continue;
                    point = IPOLSDEK_pvz.checkPoint(seltdPVZ,checks[i]);
                    if(point){
                        pret = checks[i];
                        break;
                    }
                }
                /* maybe we haven't load the point data */
                if(!pret){
                    var city = IPOLSDEK_pvz.sdekID; /*IPOLSDEK_pvz.city;*/
                    if(
                        !IPOLSDEK_pvz.profileAsked &&
                        (
                            typeof(IPOLSDEK_pvz['PVZ'][city]) === 'undefined' ||
                            typeof(IPOLSDEK_pvz['POSTAMAT'][city]) === 'undefined'
                        )
                    ){
                        IPOLSDEK_pvz.profileAsked = true;
                        $.ajax({
                            url: '/bitrix/js/ipol.sdek/ajax.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {isdek_action : 'getDataViaPointId', isdek_token : '<?=sdekHelper::getWidgetToken()?>', city : city,point : seltdPVZ},
                            success: function(data){
                                if(data.mode){
                                    IPOLSDEK_pvz.curMode = data.mode;
                                    if(typeof(IPOLSDEK_pvz[data.mode]) === 'undefined'){IPOLSDEK_pvz[data.mode] = {};}
                                    if(typeof(IPOLSDEK_pvz[data.mode][data.city]) === 'undefined'){IPOLSDEK_pvz[data.mode][data.city] = {};}
                                    IPOLSDEK_pvz[data.mode][data.city] = data.POINTS;
                                    IPOLSDEK_pvz.subLoadProfile(data.point);
                                }
                            }
                        });
                    } else {
                        return false;
                    }
                }
                else {
                    IPOLSDEK_pvz.curMode = pret;
                    IPOLSDEK_pvz.subLoadProfile(seltdPVZ);
                }
            }
        },
        /* moved because of Ajax */
        subLoadProfile : function (seltdPVZ){
            /* we choose PVZ */
            var point = IPOLSDEK_pvz.checkPoint(seltdPVZ,IPOLSDEK_pvz.curMode);
            if(point) {
                IPOLSDEK_pvz.pvzAdress = IPOLSDEK_pvz.city + ", " + point['Address'] + " #S" + seltdPVZ;
                IPOLSDEK_pvz.pvzId = seltdPVZ;

                /* adding label about PVZ info near the "Choose PVZ" button */
                for (var i in IPOLSDEK_pvz.deliveries[IPOLSDEK_pvz.curMode]) {
                    if (typeof (IPOLSDEK_pvz.deliveries[IPOLSDEK_pvz.curMode][i]) === 'function') continue;
                    if (IPOLSDEK_pvz.deliveries[IPOLSDEK_pvz.curMode][i].tag) {
                        IPOLSDEK_pvz.labelPzv(i, IPOLSDEK_pvz.curMode);
                    }
                }
            }
        },

        /* loading city PVZ */
        initCityPVZ: function(){
            var city = IPOLSDEK_pvz.sdekID; /*IPOLSDEK_pvz.city*/
            if(typeof(IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city]) === 'undefined'){
                $.ajax({
                    url: '/bitrix/js/ipol.sdek/ajax.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        isdek_action: 'getCityPvz',
                        isdek_token:  '<?=sdekHelper::getWidgetToken()?>',
                        city:         city,
                        mode:         IPOLSDEK_pvz.curMode,
                        weight:       '<?=CDeliverySDEK::$orderWeight?>',
                        goods:        <?=$orderGoodsObject?>
                    },
                    success: function(data){
                        if(typeof(IPOLSDEK_pvz[data.mode]) === 'undefined'){IPOLSDEK_pvz[data.mode] = {};}
                        if(typeof(IPOLSDEK_pvz[data.mode][data.city]) === 'undefined'){IPOLSDEK_pvz[data.mode][data.city] = {};}

                        IPOLSDEK_pvz[data.mode][data.city] = data.POINTS;

                        IPOLSDEK_pvz.prepareCityPVZContent();
                    }
                });
            } else {
                IPOLSDEK_pvz.prepareCityPVZContent();
            }
        },

        /**
         * Fills cityPVZ data, creates PVZ list HTML, init YMap with placemarks and positions da widget window
         */
        prepareCityPVZContent: function(){
            IPOLSDEK_pvz.putCityPVZ();

            if (IPOLSDEK_pvz.reinitMaps) {
                IPOLSDEK_pvz.reinitMaps = false;
                IPOLSDEK_pvz.Y_init();

                IPOLSDEK_pvz.isActive = true;
                IPOLSDEK_pvz.positWindow();

                IPOLSDEK_pvz.Y_map.container.fitToViewport();

                IPOLSDEK_pvz.scrollPVZ = $('#SDEK_wrapper').jScrollPane({autoReinitialise: true});
                $('#SDEK_preloader').css('display', 'none');
            }
        },

        putCityPVZ : function ()
        {
            var city = IPOLSDEK_pvz.sdekID; /*IPOLSDEK_pvz.city;*/
            var cnt = [];
            IPOLSDEK_pvz.cityPVZ = {};

            for(var i in IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city]){
                if(typeof(IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city][i]) === 'function') continue;
                IPOLSDEK_pvz.cityPVZ[i] = {
                    'Name'     : (IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city][i]['Name']) ? IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city][i]['Name'] : IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city][i]['Address'],
                    'Address'  : IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city][i]['Address'],
                    'WorkTime' : IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city][i]['WorkTime'],
                    'Phone'    : IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city][i]['Phone'],
                    'Note'     : IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city][i]['Note'],
                    'cX'       : IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city][i]['cX'],
                    'cY'       : IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city][i]['cY'],
                    'Dressing' : IPOLSDEK_pvz[IPOLSDEK_pvz.curMode][city][i]['Dressing']
                };
                cnt.push(i);
            }
            /* loading list of PVZs */
            IPOLSDEK_pvz.cityPVZHTML();
            IPOLSDEK_pvz.multiPVZ = (cnt.length === 1) ? cnt.pop() : false;
        },

        /* making list of city PVZ */
        cityPVZHTML: function(){
            var html = '';
            for(var i in IPOLSDEK_pvz.cityPVZ){
                if(typeof(IPOLSDEK_pvz.cityPVZ[i]) === 'function') continue;
                html+='<p id="PVZ_'+i+'" onclick="IPOLSDEK_pvz.markChosenPVZ(\''+i+'\')" onmouseover="IPOLSDEK_pvz.Y_blinkPVZ(\''+i+'\',true)" onmouseout="IPOLSDEK_pvz.Y_blinkPVZ(\''+i+'\')">'+IPOLSDEK_pvz.paintPVZ(i)+'</p>';
            }
            $('#SDEK_wrapper').html(html);
        },

        /* painting pvz, if color is given */
        paintPVZ: function(ind){
            var addr = '';
            if(IPOLSDEK_pvz.cityPVZ[ind].color && IPOLSDEK_pvz.cityPVZ[ind].Address.indexOf(',')!==false)
                addr="<span style='color:"+IPOLSDEK_pvz.cityPVZ[ind].color+"'>"+IPOLSDEK_pvz.cityPVZ[ind].Address.substr(0,IPOLSDEK_pvz.cityPVZ[ind].Address.indexOf(','))+"</span><br>"+IPOLSDEK_pvz.cityPVZ[ind].Name;
            else
                addr=IPOLSDEK_pvz.cityPVZ[ind].Name;
            return addr;
        },

        /* choosing pvz */
        pvzAdress: '',
        pvzId: false,
        choozePVZ: function(pvzId,isAjax){
            var point = IPOLSDEK_pvz.checkPoint(pvzId,IPOLSDEK_pvz.curMode);
            if(!point)
                return;

            IPOLSDEK_pvz.pvzAdress=IPOLSDEK_pvz.city+", "+point.Address+" #S"+pvzId;

            IPOLSDEK_pvz.pvzId = pvzId;

            if(typeof(KladrJsObj) !== 'undefined') KladrJsObj.FuckKladr();

            IPOLSDEK_pvz.markUnable();

            /* reloading form with new delivery price */
            if(typeof isAjax === 'undefined'){

                var htmlId = IPOLSDEK_pvz.makeHTMLId(IPOLSDEK_pvz.curProfile);
                if(typeof IPOLSDEK_DeliveryChangeEvent === 'function')
                    IPOLSDEK_DeliveryChangeEvent(htmlId);
                else{
                    if(IPOLSDEK_pvz.oldTemplate){
                        if(typeof $.prop === 'undefined')
                            $('#'+htmlId).attr('checked', 'Y');
                        else
                            $('#'+htmlId).prop('checked', 'Y');
                        $('#'+htmlId).click();
                    }else
                    if(typeof(BX.Sale) !== 'undefined' && BX.Sale.OrderAjaxComponent !== 'undefined')
                        BX.Sale.OrderAjaxComponent.sendRequest();
                }
                IPOLSDEK_pvz.close(true);
            }
        },

        markUnable: function(){
            var chznPnkt = false;
            for(var i in IPOLSDEK_pvz.pvzInputs){
                if(typeof(IPOLSDEK_pvz.pvzInputs[i]) === 'function') continue;

                chznPnkt = $('#ORDER_PROP_'+IPOLSDEK_pvz.pvzInputs[i]);
                if(chznPnkt.length<=0 || chznPnkt.get(0).tagName !== 'INPUT')
                    chznPnkt = $('[name="ORDER_PROP_'+IPOLSDEK_pvz.pvzInputs[i]+'"]');
                if(chznPnkt.length>0){
                    chznPnkt.val(IPOLSDEK_pvz.pvzAdress);
                    chznPnkt.css('background-color', '#eee').attr('readonly','readonly');
                    break;
                }
            }
        },

        /* displaying */
        close: function(fromChoose){
            <?php if(\Ipolh\SDEK\option::get('autoSelOne') == 'Y'){?>
            if(IPOLSDEK_pvz.multiPVZ !== false && typeof(fromChoose) === 'undefined')
                IPOLSDEK_pvz.choozePVZ(IPOLSDEK_pvz.multiPVZ);
            <?php }?>
            if(IPOLSDEK_pvz.scrollPVZ && typeof(IPOLSDEK_pvz.scrollPVZ.data('jsp')) !== 'undefined')
                IPOLSDEK_pvz.scrollPVZ.data('jsp').destroy();
            $('#SDEK_pvz').css('display','none');
            $('#SDEK_mask').css('display','none');
            IPOLSDEK_pvz.isActive = false;
        },

        /* clicking on button "Choose PVZ" */
        selectPVZ: function(id, mode){
            if(!IPOLSDEK_pvz.isActive){
                if(IPOLSDEK_pvz.scrollPVZ && typeof(IPOLSDEK_pvz.scrollPVZ.data) == 'function' && typeof(IPOLSDEK_pvz.scrollPVZ.data('jsp')) !== 'undefined'){
                    IPOLSDEK_pvz.scrollPVZ.data('jsp').destroy(); // TODO : not working
                }

                if(typeof(mode) === 'undefined')
                    mode = 'PVZ';
                if(IPOLSDEK_pvz.curMode != mode || !IPOLSDEK_pvz.Y_map || !IPOLSDEK_pvz.ready){
                    /* Preloader and mask */
                    $('#SDEK_preloader').css('display', 'block');
                    $('#SDEK_mask').css('display', 'block');

                    IPOLSDEK_pvz.ready = true;
                    if(IPOLSDEK_pvz.Y_map)
                        IPOLSDEK_pvz.Y_clearPVZ();
                    IPOLSDEK_pvz.curMode = mode;
                    $('[id^="SDEK_delivInfo_"]').css('display','none');
                    $('#SDEK_delivInfo_'+mode).css('display','block');

                    if(arguments.length === 1 && typeof(IPOLSDEK_pvz.deliveries[mode][id] !== 'undefined')){
                        IPOLSDEK_pvz.curProfile = id;
                    }else{
                        var link = (typeof(IPOLSDEK_pvz.deliveries[mode][id]) === 'undefined') ? IPOLSDEK_pvz.pickFirst(IPOLSDEK_pvz.deliveries[mode]) : id;
                        IPOLSDEK_pvz.curProfile = IPOLSDEK_pvz.deliveries[mode][link].link;
                    }
                    IPOLSDEK_pvz.getPrices();

                    IPOLSDEK_pvz.reinitMaps = true;

                    IPOLSDEK_pvz.initCityPVZ(); /* Da isActive flag raised and positWindow called inside initCityPVZ */

                    /* IPOLSDEK_pvz.Y_init(); moved to initCityPvz */
                } else {
                    IPOLSDEK_pvz.isActive = true;
                    IPOLSDEK_pvz.positWindow();
                    IPOLSDEK_pvz.scrollPVZ = $('#SDEK_wrapper').jScrollPane({autoReinitialise: true});
                    $('#SDEK_mask').css('display','block');
                }
            }
        },

        positWindow: function(){
            if(!IPOLSDEK_pvz.isActive) return;

            var hndlr = $('#SDEK_pvz');

            var left = ($(window).width()>hndlr.outerWidth()) ? (($(window).width()-hndlr.outerWidth())/2) : 0;

            if($(window).height() < 542){
                $('#SDEK_wrapper').css('height',hndlr.height()-82);
            }else{
                hndlr.css('height','');
                $('#SDEK_wrapper').css('height','');
            }

            hndlr.css({
                'display'   : 'block',
                'left'      : left
            });
            hndlr.css({
                'top'       : ($(window).height()-hndlr.height())/2+$(document).scrollTop()
            });

            if(typeof(IPOLSDEK_pvz.Y_map.controls) !== 'undefined'){
                var leftZK = (hndlr.width()  < 900) ? hndlr.width() - 40     : 265;
                var topZK  = (hndlr.height() < 540)	? (hndlr.height()-206)/2 : 146;
                var control = IPOLSDEK_pvz.Y_map.controls.getContainer();
                var leftSearch = 20;
                var topSearch  = 0;
                if (hndlr.width() < 880) {
                    leftSearch = 210;
                }
                if (hndlr.width() < 700) {
                    leftSearch = 210 + (700 - hndlr.width());
                    topSearch = 40;
                }
                if (hndlr.width() < 350) {
                    $(control).find('[class*="searchbox__normal-layout"]').css('width', '200px');
                } else {
                    $(control).find('[class*="searchbox__normal-layout"]').css('width', '');
                }

                if ((hndlr.width() < 620) && ($('#SDEK_pvz #SDEK_info .SDEK_all-items').css('display') === 'block') && ($('#SDEK_looper').hasClass('active'))) {
                    $('#SDEK_looper').trigger('click');
                }

                $(control).find('[class*="_control"]').css({
                    left:leftZK,
                    top: topZK
                });

                $(control).find('[class*="_toolbar"]').css({
                    left:  'auto',
                    right: leftSearch,
                    top:   topSearch
                });
            }

            if(hndlr.width() > 700)
                $('.SDEK_all-items').css('display','block');

            IPOLSDEK_pvz.togglePvzListButton();
        },

        isPvzListContainerShown: function(){
            return ($('.SDEK_all-items').css('display') === 'block');
        },

        togglePvzListButton: function(){
            if (IPOLSDEK_pvz.isPvzListContainerShown()) {
                $('#SDEK_PvzList_button').addClass('active');
            } else {
                $('#SDEK_PvzList_button').removeClass('active');
            }
        },

        scrollHintInited: false,
        markChosenPVZ: function(id){
            if(!IPOLSDEK_pvz.scrollHintInited){
                IPOLSDEK_pvz.scrollHintInited = true;
                window.setTimeout(IPOLSDEK_pvz.makeScrollHint,100);
            }
            if($('.sdek_chosen').attr('id') !== 'PVZ_'+id){
                $('.sdek_chosen').removeClass('sdek_chosen');
                $("#PVZ_"+id).addClass('sdek_chosen');
                IPOLSDEK_pvz.Y_selectPVZ(id);
            }
            if($('#SDEK_pvz').width() < 450 && $('.SDEK_all-items').css('display') !== 'none')
                IPOLSDEK_pvz.handleArrow();
        },

        makeScrollHint: function(){
            $('.sdek_baloonInfo').jScrollPane({contentWidth: '0px',autoReinitialise:true});
            IPOLSDEK_pvz.scrollHintInited = false;
        },

        handleArrow: function(){
            if (!IPOLSDEK_pvz.isPvzListContainerShown()) {
                $('#SDEK_PvzList_button').addClass('active');
                $('.SDEK_all-items').slideDown();
                if (($('#SDEK_pvz').width() < 620) && ($('#SDEK_looper').hasClass('active')))
                    IPOLSDEK_pvz.Y_turnSearch();
            } else {
                $('#SDEK_PvzList_button').removeClass('active');
                $('.SDEK_all-items').slideUp();
            }
        },

        /* Y-maps */
        Y_map: false,

        Y_init: function(){
            IPOLSDEK_pvz.Y_readyToBlink = false;
            if(typeof IPOLSDEK_pvz.city === 'undefined')
                IPOLSDEK_pvz.city = '<?=GetMessage('IPOLSDEK_FRNT_MOSCOW')?>';
            var pvzCoords = IPOLSDEK_pvz.Y_getPVZCenters();

            if(pvzCoords){
                IPOLSDEK_pvz.Y_initCityMap(pvzCoords);
            } else {
                var country = (typeof(IPOLSDEK_pvz.cityCountry[IPOLSDEK_pvz.city]) === 'undefined') ? "<?=GetMessage("IPOLSDEK_RUSSIA")?>" : IPOLSDEK_pvz.cityCountry[IPOLSDEK_pvz.city];
                ymaps.geocode(country+", "+IPOLSDEK_pvz.city , {
                    results: 1
                }).then(function (res) {
                    var firstGeoObject = res.geoObjects.get(0);
                    var coords = firstGeoObject.geometry.getCoordinates();
                    IPOLSDEK_pvz.Y_initCityMap(coords);
                });
            }

            IPOLSDEK_pvz.togglePvzListButton();
        },

        Y_initCityMap : function(coords){
            var checker = $('#SDEK_pvz').width();

            coords[1]-=(checker > 700) ? 0.2 : -(120 / checker);
            if(!IPOLSDEK_pvz.Y_map){
                IPOLSDEK_pvz.Y_map = new ymaps.Map("SDEK_map",{
                    zoom:10,
                    controls: [],
                    center: coords
                });

                var hCheck = $('#SDEK_pvz').height();

                var ZK = new ymaps.control.ZoomControl({
                    options : {
                        position:{
                            left : (checker > 700) ? 265 : checker - 40,
                            top  : (hCheck > 540)  ? 146  : (hCheck - 206)/2
                        }
                    }
                });

                IPOLSDEK_pvz.Y_map.controls.add(ZK);
            }else{
                IPOLSDEK_pvz.Y_map.setCenter(coords);
                IPOLSDEK_pvz.Y_map.setZoom(10);
            }
            IPOLSDEK_pvz.Y_clearPVZ();
            IPOLSDEK_pvz.Y_markPVZ();
        },

        Y_getPVZCenters : function(){
            var ret = [0,0,0];
            for(var i in IPOLSDEK_pvz.cityPVZ){
                if(
                    typeof(IPOLSDEK_pvz.cityPVZ[i].cX) !== 'undefined' &&
                    typeof(IPOLSDEK_pvz.cityPVZ[i].cY) !== 'undefined' &&
                    IPOLSDEK_pvz.cityPVZ[i].cX && IPOLSDEK_pvz.cityPVZ[i].cY
                ){
                    ret[0] += parseFloat(IPOLSDEK_pvz.cityPVZ[i].cY);
                    ret[1] += parseFloat(IPOLSDEK_pvz.cityPVZ[i].cX);
                    ret[2] ++;
                }
            }

            if(ret[2]){
                ret[0] /= ret[2];
                ret[1] /= ret[2];
                ret.pop();
                return ret;
            } else {
                return false;
            }
        },

        Y_markPVZ: function(){
            for(var i in IPOLSDEK_pvz.cityPVZ){
                var baloonHTML  = "<div id='SDEK_baloon'>";
                baloonHTML += "<div class='SDEK_iAdress'>";
                if(IPOLSDEK_pvz.cityPVZ[i].Address.indexOf(',')!==-1){
                    if(IPOLSDEK_pvz.cityPVZ[i].color)
                        baloonHTML +=  "<span style='color:"+IPOLSDEK_pvz.cityPVZ[i].color+"'>"+IPOLSDEK_pvz.cityPVZ[i].Address.substr(0,IPOLSDEK_pvz.cityPVZ[i].Address.indexOf(','))+"</span>";
                    else
                        baloonHTML +=  IPOLSDEK_pvz.cityPVZ[i].Address.substr(0,IPOLSDEK_pvz.cityPVZ[i].Address.indexOf(','));
                    baloonHTML += "<br>"+IPOLSDEK_pvz.cityPVZ[i].Address.substr(IPOLSDEK_pvz.cityPVZ[i].Address.indexOf(',')+1).trim();
                }
                else
                    baloonHTML += IPOLSDEK_pvz.cityPVZ[i].Address;
                baloonHTML += "</div>";

                if(IPOLSDEK_pvz.cityPVZ[i].Phone)
                    baloonHTML += "<div><div class='SDEK_iTelephone sdek_icon'></div><div class='sdek_baloonDiv'>"+IPOLSDEK_pvz.cityPVZ[i].Phone+"</div><div style='clear:both'></div></div>";
                if(IPOLSDEK_pvz.cityPVZ[i].WorkTime)
                    baloonHTML += "<div><div class='SDEK_iTime sdek_icon'></div><div class='sdek_baloonDiv'>"+IPOLSDEK_pvz.cityPVZ[i].WorkTime+"</div><div style='clear:both'></div></div>";
                if(IPOLSDEK_pvz.cityPVZ[i].Dressing){
                    baloonHTML += "<div><div class='SDEK_iDressing sdek_icon'></div><div class='sdek_baloonDiv'><?=GetMessage('IPOLSDEK_FRNT_DRESSING')?></div><div style='clear:both'></div></div>";
                }

                if(IPOLSDEK_pvz.cityPVZ[i].Note)
                    baloonHTML += "<div class='sdek_baloonInfo'><div>"+IPOLSDEK_pvz.cityPVZ[i].Note+"</div></div><div style='clear:both'></div>";
                baloonHTML += "<div><a id='SDEK_button' href='javascript:void(0)' onclick='IPOLSDEK_pvz.choozePVZ(\""+i+"\")'></a></div>";
                baloonHTML += "</div>";
                IPOLSDEK_pvz.cityPVZ[i].placeMark = new ymaps.Placemark([IPOLSDEK_pvz.cityPVZ[i].cY,IPOLSDEK_pvz.cityPVZ[i].cX],{
                    balloonContent: baloonHTML
                }, {
                    iconLayout: 'default#image',
                    iconImageHref: '/bitrix/images/ipol.sdek/widjet/sdekNActive.png',
                    iconImageSize: [40, 43],
                    iconImageOffset: [-10, -31]
                });
                IPOLSDEK_pvz.Y_map.geoObjects.add(IPOLSDEK_pvz.cityPVZ[i].placeMark);
                IPOLSDEK_pvz.cityPVZ[i].placeMark.link = i;
                IPOLSDEK_pvz.cityPVZ[i].placeMark.events.add('balloonopen',function(metka){
                    IPOLSDEK_pvz.markChosenPVZ(metka.get('target').link);
                });
            }
            IPOLSDEK_pvz.Y_readyToBlink = true;
        },

        Y_selectPVZ: function(wat){
            var checker = $('#SDEK_pvz').width();
            var adr = (checker > 700) ? 0.2 : -(120 / checker);
            IPOLSDEK_pvz.Y_map.setCenter([IPOLSDEK_pvz.cityPVZ[wat].cY,parseFloat(IPOLSDEK_pvz.cityPVZ[wat].cX)-adr]);
            IPOLSDEK_pvz.cityPVZ[wat].placeMark.balloon.open();
        },

        Y_readyToBlink: false,
        Y_blinkPVZ: function(wat,ifOn){
            if(IPOLSDEK_pvz.Y_readyToBlink){
                if(typeof(ifOn)!=='undefined' && ifOn)
                    IPOLSDEK_pvz.cityPVZ[wat].placeMark.options.set({iconImageHref:"/bitrix/images/ipol.sdek/widjet/sdekActive.png"});
                else
                    IPOLSDEK_pvz.cityPVZ[wat].placeMark.options.set({iconImageHref:"/bitrix/images/ipol.sdek/widjet/sdekNActive.png"});
            }
        },

        Y_clearPVZ: function(){
            if(typeof(IPOLSDEK_pvz.Y_map.geoObjects.removeAll) !== 'undefined' && false)
                IPOLSDEK_pvz.Y_map.geoObjects.removeAll();
            else{
                do{
                    IPOLSDEK_pvz.Y_map.geoObjects.each(function(e){
                        IPOLSDEK_pvz.Y_map.geoObjects.remove(e);
                    });
                }while(IPOLSDEK_pvz.Y_map.geoObjects.getBounds());
            }
        },

        Y_zoomCalibrate: function(){
            while(!ymaps.geoQuery(map.geoObjects).searchInside(IPOLSDEK_pvz.Y_map).getLength() && IPOLSDEK_pvz.Y_map.getZoom()> 4)
            {
                IPOLSDEK_pvz.Y_map.setZoom(IPOLSDEK_pvz.Y_map.getZoom()-1);
            }
        },

        Y_turnSearch: function(){
            if(IPOLSDEK_pvz.Y_map){
                if(!IPOLSDEK_pvz.Y_map.controls.get('searchControl')){
                    IPOLSDEK_pvz.Y_map.controls.add('searchControl', {float: 'right', floatIndex: 100, noPlacemark: true });
                    IPOLSDEK_pvz.Y_map.controls.events.add('resultshow', IPOLSDEK_pvz.Y_zoomCalibrate, IPOLSDEK_pvz.Y_map.controls.get('searchControl'));
                    $('#SDEK_looper').addClass('active');
                    if (($('#SDEK_pvz').width() < 620) && ($('#SDEK_PvzList_button').hasClass('active')))
                        IPOLSDEK_pvz.handleArrow();
                } else {
                    IPOLSDEK_pvz.Y_map.controls.events.remove('resultshow', IPOLSDEK_pvz.Y_zoomCalibrate, IPOLSDEK_pvz.Y_map.controls.get('searchControl'));
                    IPOLSDEK_pvz.Y_map.controls.remove('searchControl');
                    $('#SDEK_looper').removeClass('active');
                }
            }
        },

        /* loading */
        readySt: {
            ymaps: false,
            jqui: false
        },
        inited: false,
        checkReady: function(wat){
            if(typeof(IPOLSDEK_pvz.readySt[wat]) !== 'undefined')
                IPOLSDEK_pvz.readySt[wat] = true;
            if(IPOLSDEK_pvz.readySt.ymaps && (IPOLSDEK_pvz.readySt.jqui || typeof($) !== 'undefined') && !IPOLSDEK_pvz.inited){
                IPOLSDEK_pvz.inited = true;
                var tmpHTML = $('#SDEK_pvz').html();
                $('#SDEK_pvz').replaceWith('');
                $('body').append("<div id='SDEK_pvz'>"+tmpHTML+"</div>");
                IPOLSDEK_pvz.init();
            }
        },

        jquiready: function(){IPOLSDEK_pvz.checkReady('jqui');},
        ympsready: function(){IPOLSDEK_pvz.checkReady('ymaps');},

        ymapsBindCntr  : 0,
        ymapsBindFinal : false,
        ymapsBlockLoad : false,
        ymapsBidner: function(){
            if(IPOLSDEK_pvz.ymapsBlockLoad){
                return;
            }
            if(IPOLSDEK_pvz.ymapsBindCntr > 50){
                if(IPOLSDEK_pvz.ymapsBindFinal){
                    console.error('SDEK widjet error: no Y-maps');
                    return;
                } else {
                    IPOLSDEK_pvz.ymapsBindFinal = true;
                    IPOLSDEK_pvz.ymapsBlockLoad = true;
                    IPOL_JSloader.checkScript('','<?=$pathToYmaps?>',IPOLSDEK_pvz.ymapsForseCheck);
                    IPOL_JSloader.recall();
                    return;
                }
            }
            if(typeof(ymaps) === 'undefined'){
                IPOLSDEK_pvz.ymapsBindCntr++;
                setTimeout(IPOLSDEK_pvz.ymapsBidner,100);
            }else
                ymaps.ready(IPOLSDEK_pvz.ympsready);
        },
        ymapsForseCheck: function(){
            IPOLSDEK_pvz.ymapsBindCntr = 0;
            IPOLSDEK_pvz.ymapsBlockLoad = false;
            IPOLSDEK_pvz.ymapsBidner();
        },
        /* service */
        isFull: function(wat){
            if(typeof(wat) !== 'object') return (wat);
            else
                for(var i in wat)
                    return true;
            return false;
        }
    };
    IPOLSDEK_pvz.ymapsBidner();
    IPOL_JSloader.checkScript('',"/bitrix/js/<?=CDeliverySDEK::$MODULE_ID?>/jquery.mousewheel.js");
    IPOL_JSloader.checkScript('$("body").jScrollPane',"/bitrix/js/<?=CDeliverySDEK::$MODULE_ID?>/jquery.jscrollpane.js",IPOLSDEK_pvz.jquiready);
</script>
<?php /* HTML of the vidjet */?>
<div id='SDEK_pvz'>
    <div id='SDEK_head'>
        <div id='SDEK_logo'><a href='http://ipol.ru' rel='nofollow' target='_blank'></a></div>
        <div id="SDEK_PvzList_button" onclick="IPOLSDEK_pvz.handleArrow()"></div>
        <?php if($arParams['SEARCH_ADDRESS'] === 'Y'){?>
            <div id="SDEK_looper" onclick="IPOLSDEK_pvz.Y_turnSearch()"></div>
        <?php }?>
        <div id='SDEK_closer' onclick='IPOLSDEK_pvz.close()'></div>
    </div>
    <div id='SDEK_map'></div>
    <div id='SDEK_info'>
        <div id='SDEK_sign'><span><?=GetMessage("IPOLSDEK_LABELPVZ")?></span></div>
        <div id='SDEK_delivInfo_PVZ'><?=GetMessage("IPOLSDEK_CMP_PRICE")?>
            <span id='SDEK_pPrice'></span>,&nbsp;<?=GetMessage("IPOLSDEK_CMP_TRM")?>
            <span id='SDEK_pDate'></span>
        </div>
        <div class="SDEK_all-items">
            <div id='SDEK_wrapper'></div>
            <div id='SDEK_ten'></div>
        </div>
    </div>
</div>