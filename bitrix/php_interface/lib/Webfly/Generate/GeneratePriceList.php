<?php namespace Webfly\Generate;

use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\PriceTable;
use Spipu\Html2Pdf\Locale;


class GeneratePriceList
{
    protected $iblockId = 93;
    protected $sectIdsPack = [1437 => 1437, 1445 => 1445, 1421 => 1421, 1821 => 1821];
    protected $arData = [];

    protected $html = '';
    protected $style = '';
    protected $script = '';
    public $path = '';
    public $pathAllSect = '';
    public $pathPdfStatic = '';
    public $pathHtmlStatic = '';


    function __construct($root = '')
    {

        if (!empty($root)) $_SERVER['DOCUMENT_ROOT'] = $root;
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/upload/price-list-files/';
        $this->pathAllSect = $_SERVER['DOCUMENT_ROOT'] . '/upload/price-list-files/sections/';
        $this->pathPdfStatic = $_SERVER['DOCUMENT_ROOT'] . '/upload/price-list-files/pdf-static/';
        $this->pathHtmlStatic = $_SERVER['DOCUMENT_ROOT'] . '/upload/price-list-files/html-static/';
        $this->style = "body:has(.price_list.first) {min-height: calc(var(--vh, 1vh)* 100);width: 100%;display: flex;flex-direction: column;justify-content: space-between;} .actualize {position: absolute;bottom: 48px;right: 48px;font-size: 28px;line-height: 35px;font-weight: 600;color: #fff;width: -moz-fit-content;width: fit-content;} 
        .price_list.first{
        position:relative;
        color:white; 
        height: 100%;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content:center;
        max-height:1279px
        }
        .price_list.first > img {
            position:absolute;
            top:0;
            left:0;
            width:100%;
            height:100%;
            display:block;
            object-fit:cover;
        }.price_list .last_slide,.price_list.first .title_block{top:50%;left:50%;transform:translate(-50%,-50%);position:absolute}.price_list *{font-family:Montserrat}.price_list{position:relative;max-width:1920px;width:100%;margin:0 auto}.price_list.first .title_block{display:flex;flex-direction:column;align-items:center;text-align:center;gap:40px}.price_list.first .title_block .title{font-size:120px;font-weight:600;line-height:132px}.price_list.first .title_block .subtitle{font-size:48px;font-weight:600;line-height:60px;max-width:1200px}.price_list.first .connect{position:absolute;bottom:100px;left:50%;transform:translateX(-50%);width:fit-content;display:grid;grid-template-columns:180px 1fr;align-items:center;gap:48px}.price_list.first .connect>img{width:180px;height:180px;display:block;object-fit:contain}.price_list.first .connect ul{list-style:none;display:flex;flex-direction:column;gap:4px}.price_list.first .connect ul li{display:flex;align-items:center;gap:16px}.price_list.first .connect ul li img{width:48px;height:48px;display:block;object-fit:contain}.price_list.first .connect ul li p{font-size:40px;line-height:60px;font-weight:600}.list_title,.price_list .last_slide .title{font-size:80px;font-weight:600;line-height:96px}.price_list .product_list{display:grid;grid-template-columns:repeat(3,1fr);gap:48px 40px;list-style:none;margin-top:80px}.price_list .product_list li img{width:100%;aspect-ratio:547/307;display:block;object-fit:cover}.price_list .product_list li .suptitle{margin-top:24px;font-size:16px;line-height:20px;font-weight:600;text-transform:uppercase}.price_list .product_list li .title{margin-top:4px;font-size:48px;line-height:58px;font-weight:600}.price_list .text-md{font-size:24px;line-height:28px;font-weight:600}.price_list .content_grid .text_content .title,.price_list .text-lg{line-height:58px;font-size:48px;font-weight:600}.price_list .bg-image .head-grid{display:flex;justify-content:space-between}.price_list .bg-image .head-grid ul{list-style:none;display:flex;flex-direction:column;gap:64px;max-width:705px}.price_list .bg-image .head-grid ul li{display:flex;flex-direction:column;gap:12px}.price_list .bg-image .head-grid ul li .head{display:flex;align-items:center;gap:12px}.price_list .bg-image .head-grid ul li .title{font-size:40px;line-height:48px;font-weight:600}.price_list ul.meta{list-style:none;display:flex;justify-content:space-between;margin-top:100px}.price_list ul.meta li{display:flex;flex-direction:column;align-items:center;gap:8px;width:220px}.price_list .content_grid .text_content,.price_list .content_grid .text_content ul{display:flex;flex-direction:column;gap:40px}.price_list ul.meta li img{height:112px;display:block}.price_list ul.meta li p{text-align:center;width:300px}.price_list .content_grid{display:grid;grid-template-columns:1fr 980px;gap:40px;margin-top:80px}.price_list .last_slide ul,.price_list .list_grid{grid-template-columns:repeat(4,1fr);list-style:none;display:grid}.price_list .content_grid .text_content .page{font-size:48px;font-weight:600;line-height:76px;margin-top:auto}.price_list .content_grid .text_content ul{padding-left:24px}.price_list .content_grid .text_content ul li{line-height:38px}.price_list .content_grid .text_content img{width:100%;aspect-ratio:980/976;display:block;object-fit:cover}.price_list .list_grid{gap:40px;margin-top:80px}.price_list .list_grid li img{width:100%;aspect-ratio:4/5;display:block;object-fit:cover}.price_list .list_grid li .suptitle{margin-top:48px;font-size:16px;font-weight:600;line-height:20px}.price_list .list_grid li .title{margin-top:4px;font-size:48px;font-weight:600;line-height:58px}.price_list .list_grid li .text-md{margin-top:24px}.price_list .last_slide{display:flex;flex-direction:column;align-items:center;gap:40px;max-width:987px}.price_list .last_slide .text-md{text-align:center}.price_list .last_slide ul li img{width:220px;aspect-ratio:222/74;display:block;object-fit:cover}.price_list .table_head{display:flex;align-items:center;justify-content:space-between;padding-bottom:40px}.price_list .list_title+.table_head,.price_list .table+.table_head{padding-top:64px}.price_list .table_coffee,.price_list .table_other,.price_list .table_tea{width:100%}.price_list .table_tea .tbody .tr,.price_list .table_tea .thead .tr{list-style:none;display:grid;grid-template-columns:800px 148px 148px 148px 148px;justify-content:space-between}.price_list .table_coffee .tbody .tr,.price_list .table_coffee .thead .tr{list-style:none;display:grid;grid-template-columns:800px 250px 148px 148px 148px 148px;justify-content:space-between}.price_list .table_other .tbody .tr,.price_list .table_other .thead .tr{list-style:none;display:grid;grid-template-columns:600px 900px 1fr;justify-content:space-between}.price_list .table_coffee .thead .tr,.price_list .table_other .thead .tr,.price_list .table_tea .thead .tr{padding-bottom:16px;border-bottom:1px solid #000}.price_list .table_coffee .thead .tr li,.price_list .table_other .thead .tr li,.price_list .table_tea .thead .tr li{text-align:left;font-size:20px;font-weight:600;line-height:24px}.price_list .table_coffee .tbody .tr,.price_list .table_other .tbody .tr,.price_list .table_tea .tbody .tr{padding-block:16px;border-bottom:1px solid #000}.price_list .table_coffee .tbody .tr .text-sm,.price_list .table_other .tbody .tr .text-sm,.price_list .table_tea .tbody .tr .text-sm{font-size:20px;font-weight:600;line-height:24px;margin-bottom:8px}.price_list .table_coffee .tbody .tr .text-caption,.price_list .table_other .tbody .tr .text-caption,.price_list .table_tea .tbody .tr .text-caption{max-width:800px;font-size:16px;font-weight:400;line-height:20px}.price_list .table_coffee .tbody .tr .text-default,.price_list .table_other .tbody .tr .text-default,.price_list .table_tea .tbody .tr .text-default{font-size:18px;font-weight:500;line-height:22px}.price_list .table_coffee .tbody .tr .text-caption.thin,.price_list .table_other .tbody .tr .text-caption.thin,.price_list .table_tea .tbody .tr .text-caption.thin{font-weight:300;margin-top:12px}.price_list .table_coffee .tbody .tr .line,.price_list .table_other .tbody .tr .line,.price_list .table_tea .tbody .tr .line{position:relative;width:100%;height:6px;background-color:#c6c6c6}.price_list .table_coffee .tbody .tr .line .fill,.price_list .table_other .tbody .tr .line .fill,.price_list .table_tea .tbody .tr .line .fill{position:absolute;top:0;left:0;z-index:2;height:100%;background-color:#d33836}.price_list .table_coffee .tbody .tr .line::after,.price_list .table_other .tbody .tr .line::after,.price_list .table_tea .tbody .tr .line::after{content:'';position:absolute;top:0;left:50%;z-index:1;transform:translateX(-50%);height:100%;width:2px;background-color:#fff}.price_list .table_coffee .tbody .tr .text-sm-2,.price_list .table_other .tbody .tr .text-sm-2,.price_list .table_tea .tbody .tr .text-sm-2{font-size:14px;line-height:16px;font-weight:300}.price_list .table_coffee .tbody .tr .text-sm-2+.text-sm-2,.price_list .table_other .tbody .tr .text-sm-2+.text-sm-2,.price_list .table_tea .tbody .tr .text-sm-2+.text-sm-2{margin-top:10px}*{box-sizing:border-box;margin:0;padding:0;outline:0;text-decoration:none}.table_holder{padding:100px 48px}.table_holder .table_head:first-child{margin-top:0!important}.table_holder.multi+.table_holder.multi{padding-top:0}
        .labels{display:flex;flex-wrap:wrap;}
        .labels .label{margin:4px;border-radius:13px;font-size:12px;padding:4px 8px;color:#4f4f4f;font-weight:500;line-height:150%;display:flex;align-items:center;isolation:isolate;overflow:hidden;}
        .labels .label .icon{margin-right:4px;width:16px;height:16px;display:block;object-fit:contain;}
        .labels .label.grey-noborder{background: #f2f2f2;}
        .labels .label.green{background:#F0F9F6;border:1px solid #0AA250;}
        .labels .label.green span{color: #0AA250;}
        .labels .label.red{background:#FDF0F1;border:1px solid #D33836;}
        .labels .label.red span{color: #D33836;}
        .labels .label.yellow{background:#FEFAEA;border:1px solid #C68811;}
        .labels .label.yellow span{color: #C68811;}
        .labels .label.grey{background:#F9F9F9;border:1px solid #979797;}
        .labels .label.grey span{color: #979797;}
        .labels .label.dark{background:#F9F9F9;border:1px solid #4F4F4F;position:relative;cursor:pointer;}
        .labels .label.dark span{color: #4F4F4F;}
        ";
    }

    public
    function execute($type = '')
    {
        if (empty($type)) {
            $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
            $type = $request->get("action");
        }

        if ($type == 'generateHTML') {
            $this->generateHTML();
            $this->createStaticHTML('start_price_list1');
        }

        if ($type == 'generatePDF') {
            require_once $this->path . "price-list.html";
        }

        if ($type == 'mergePDF') {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->pathAllSect, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $dirPdf = $item->getPathname();
                    $this->mergePDF($dirPdf);
                }
            }
            $this->deletePdfSection();
        }
    }

    function deletePdfSection()
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->pathAllSect, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $dirPdf = $item->getPathname();
                $files = glob($dirPdf . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) unlink($file);
                }
            }
        }
    }

    function generateHTML()
    {
        $this->getInfo();
        $this->formatedHTML();
        $this->createHTML('price-list', $this->path);
    }

    function createStaticHTML($nameFile = 'test')
    {
        $objDateTime = new \DateTime();
        $date = $objDateTime->format('j');
        $date .= FormatDateFromDB($objDateTime->format('m.Y'), ' MMMM YYYY');


        $this->html = '<main class="price_list first">
            <img src="https://legend-tea.ru/bitrix/templates/b2b/assets/static/img/price_list/price_list_1.png">
			<div class="title_block">
				<p class="title">Прайс-лист</p>
				<p class="subtitle">Оптовые поставки кофе и чая собственного производства и с собственного склада</p>
			</div>

			<div class="connect">
				<img src="https://legend-tea.ru/bitrix/templates/b2b/assets/static/img/price_list/qr.svg" alt="">
				<ul>
					<li>
						<img src="https://legend-tea.ru/bitrix/templates/b2b/assets/static/img/price_list/call.svg" alt="">
						<p>8 (800) 700-78-87</p>
					</li>
					<li>
						<img src="https://legend-tea.ru/bitrix/templates/b2b/assets/static/img/price_list/mail.svg" alt="">
						<p>zakaz@legend-tea.ru</p>
					</li>
					<li>
						<img src="https://legend-tea.ru/bitrix/templates/b2b/assets/static/img/price_list/web.svg" alt="">
						<p>legend-tea.ru</p>
					</li>
				</ul>
			</div>
			<p class="actualize">Актуален на ' . $date . '</p>
		</main>';
        $this->script = <<<EOD
    <script>
     function fileGenerate() {
        let jspdf = new jsPDF({
            unit: "px",
            orientation: "landscape",
            format: [960, 640],
            compress: true,
        });
     //   jspdf.setFillColor("White");
      //  jspdf.internal.scaleFactor = 2;
        let options = {};

        jspdf.addHTML(document.querySelectorAll(`.price_list`), options, () => {
            let pdf = jspdf.output('blob')
            const file = new File(
                [pdf],
               '{$nameFile}' + '.pdf',
                {type: 'application/pdf'}
            );
            var data = new FormData();
            data.append('file', file);
            data.append('static', 'Y');
         
            fetch('https://legend-tea.ru/ajax/upload-price-list.php', {
                method: 'POST',
                body: data,
            })
                .then(response => response.text())
                .then(result => {
                    console.log('Success:', result);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        fileGenerate();
    });
    </script>
    EOD;

        $this->createHTML($nameFile, $this->pathHtmlStatic);
    }

    function mergePDF($dirPdf)
    {
        $filesPdf[] = $this->path . 'pdf-static/start_price_list.pdf';
        $dir = new \DirectoryIterator($dirPdf);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $filesPdf[preg_replace('/[^0-9]+/', '', $fileinfo->getFilename())] = $fileinfo->getPathname();
            }
        }
        ksort($filesPdf);
        array_unshift($filesPdf, $this->path . 'pdf-static/start_price_list1.pdf');
        $filesPdf[count($filesPdf)] = $this->path . 'pdf-static/end_price_list.pdf';

        $arStr = explode('/', $dirPdf);
        $sectCode = $arStr[count($arStr) - 1];

        if (!empty($filesPdf)) {
            $pdf = new \setasign\Fpdi\Fpdi();
            foreach ($filesPdf as $file) {
                $pageCount = $pdf->setSourceFile($file);
                for ($i = 0; $i < $pageCount; $i++) {
                    $tpl = $pdf->importPage($i + 1, '/MediaBox');
                    $pdf->addPage('l');
                    $pdf->useTemplate($tpl, 0, 0, 300, 210);
                }
            }
            $pdf->Output('F', $this->path . $sectCode . '-price-list.pdf');
        }
    }

    function getSectionList($filter, $select)
    {
        $dbSection = \CIBlockSection::GetList(
            array(
                'LEFT_MARGIN' => 'ASC',
            ),
            array_merge(
                array(
                    'ACTIVE' => 'Y',
                    'GLOBAL_ACTIVE' => 'Y'
                ),
                is_array($filter) ? $filter : array()
            ),
            false,
            array_merge(
                array(
                    'ID',
                    'IBLOCK_SECTION_ID',
                    'DEPTH_LEVEL',
                    'CODE'
                ),
                is_array($select) ? $select : array()
            )
        );
        while ($arSection = $dbSection->GetNext(true, false)) {
            $SID = $arSection['ID'];
            $PSID = (int)$arSection['IBLOCK_SECTION_ID'];
            $arLincs[$PSID]['SECT'][$SID] = $arSection;
            $arLincs[$SID] = &$arLincs[$PSID]['SECT'][$SID];
        }
        return array_shift($arLincs)['SECT'];
    }

    public function getInfo()
    {
        if (!\CModule::IncludeModule("catalog")) return false;
        if (!\CModule::IncludeModule("iblock")) return false;

        $sectIDs = [];
        $sectIdsPack = [];
        $this->arData = $this->getSectionList(['ACTIVE' => 'Y', 'IBLOCK_ID' => $this->iblockId], ['ID', 'NAME', 'DEPTH_LEVEL']);
        if (!empty($this->arData)) {
            foreach ($this->arData as $key => $sect) {
                $sectIDs = array_merge($sectIDs, array_keys($sect['SECT']));
                if (in_array($sect['ID'], $this->sectIdsPack)) {
                    $sectIdsPack = array_merge($sectIdsPack, array_keys($sect['SECT']));
                }
            }
        }

        $sectIdsNoPack = array_diff($sectIDs, $sectIdsPack);

        if ($sectIDs) {
            $dbItems = \Bitrix\Iblock\ElementTable::getList(array(
                'order' => ['NAME' => 'asc'],
                'select' => array('NAME', 'ID', 'IBLOCK_SECTION_ID', 'TYPE' => 'PRODUCT.TYPE', 'PRICE_VALUE' => 'PRICE.PRICE', 'GROUP_ID' => 'PRICE.CATALOG_GROUP_ID', 'PROP_' => 'PROP',
                ),
                'filter' => array(
                    'IBLOCK_ID' => 93,
                    'ACTIVE' => 'Y',
                    'IBLOCK_SECTION_ID' => $sectIDs,
                    [
                        "LOGIC" => "OR",
                        ['PRODUCT.TYPE' => 3,],
                        ['PRODUCT.TYPE' => 1, 'PRICE.CATALOG_GROUP_ID' => [24, 25, 26, 27]],
                    ]
                ),
                'runtime' => array(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PRICE',
                        '\Bitrix\Catalog\PriceTable',
                        ['=this.ID' => 'ref.PRODUCT_ID'],
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PRODUCT',
                        '\Bitrix\Catalog\ProductTable',
                        ['=this.ID' => 'ref.ID'],
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PROP',
                        '\Bitrix\Iblock\ElementPropertyTable',
                        ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID',]
                    ),
                ),
            ))->fetchAll();
            $dbProp = \Bitrix\Iblock\PropertyEnumTable::getList(array(
                'order' => ['ID' => 'asc'],
                'select' => ['ID', 'PROPERTY_ID', 'VALUE'],
                'filter' => ['PROPERTY_ID' => ['2170', '2172', '1767', '1875'],]

            ))->fetchAll();

            if ($sectIdsNoPack && $sectIdsPack) {
                $dbItemsTp = \Bitrix\Iblock\ElementTable::getList(array(
                    'order' => ['TYPE' => 'desc'],
                    'select' => array('NAME', 'ID', 'ELEM_ID' => 'PARENT_ELEMENT.ID', 'ELEM_NAME' => 'PARENT_ELEMENT.NAME', 'IBLOCK_SECTION_ID' => 'PARENT_ELEMENT.IBLOCK_SECTION_ID', 'PRICE_VALUE' => 'PRICE.PRICE', 'GROUP_ID' => 'PRICE.CATALOG_GROUP_ID', 'TYPE' => 'PRODUCT.TYPE', 'PROP_' => 'PROP',
                    ),
                    'filter' => array(
                        'IBLOCK_ID' => [93, 94],
                        'ACTIVE' => 'Y',
                        'PARENT_ELEMENT.ACTIVE' => 'Y',
                        'PRICE.CATALOG_GROUP_ID' => [24, 25, 26, 27],
                        [
                            'LOGIC' => 'OR',
                            [
                                'IBLOCK_SECTION_ID' => $sectIdsPack,
                                'PROP_IBLOCK_PROPERTY_ID' => 1812,
                                'PROP_VALUE' => 5856 // ИД  значения списка 1000г
                            ],
                            ['IBLOCK_SECTION_ID' => $sectIdsNoPack,],
                        ],
                    ),
                    'runtime' => array(
                        new \Bitrix\Main\Entity\ReferenceField(
                            'LINK',
                            '\Bitrix\Iblock\ElementPropertyTable',
                            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID',]
                        ),
                        new \Bitrix\Main\Entity\ReferenceField(
                            'PARENT_ELEMENT',
                            '\Bitrix\Iblock\ElementTable',
                            ['=this.LINK.VALUE' => 'ref.ID',]
                        ),
                        new \Bitrix\Main\Entity\ReferenceField(
                            'PRICE',
                            '\Bitrix\Catalog\PriceTable',
                            ['=this.ID' => 'ref.PRODUCT_ID'],
                        ),
                        new \Bitrix\Main\Entity\ReferenceField(
                            'PRODUCT',
                            '\Bitrix\Catalog\ProductTable',
                            ['=this.ID' => 'ref.ID'],
                        ),
                        new \Bitrix\Main\Entity\ReferenceField(
                            'PROP',
                            '\Bitrix\Iblock\ElementPropertyTable',
                            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID',]
                        ),
                    ),
                ))->fetchAll();

                $arProps = [];
                foreach ($dbProp as $prop) {
                    $item[$prop['ID']] = $prop['VALUE'];
                    $arProps[$prop['PROPERTY_ID']][$prop['ID']] = $item[$prop['ID']];
                }
            }
        }

        if (!empty($dbItems)) {
            $arProp = [];
            foreach ($dbItems as $item) {
                foreach ($this->arData as $idSect => $sect) {
                    if ($sect['SECT'][$item['IBLOCK_SECTION_ID']]) {
                        $arProp[$item['ID']]['ID'] = $item["ID"];
                        $arProp[$item['ID']]['NAME'] = $item["NAME"];
                        $arProp[$item['ID']]['IBLOCK_SECTION_ID'] = $item["IBLOCK_SECTION_ID"];
                        $arProp[$item['ID']]['TYPE'] = $item["TYPE"];

                        if ($item['GROUP_ID'] == '24') $arProp[$item['ID']]['PRICE_1'] = $item["PRICE_VALUE"] . ' ₽';
                        if ($item['GROUP_ID'] == '25') $arProp[$item['ID']]['PRICE_2'] = $item["PRICE_VALUE"] . ' ₽';
                        if ($item['GROUP_ID'] == '26') $arProp[$item['ID']]['PRICE_3'] = $item["PRICE_VALUE"] . ' ₽';
                        if ($item['GROUP_ID'] == '27') $arProp[$item['ID']]['PRICE_4'] = $item["PRICE_VALUE"] . ' ₽';

                        if ($item['PROP_IBLOCK_PROPERTY_ID'] == '1751') {
                            $arProp[$item['ID']]['ARTICLE'] = $item["PROP_VALUE"];
                        }
                        if ($item['PROP_IBLOCK_PROPERTY_ID'] == '1776') {
                            $arProp[$item['ID']]['OPISANIE_ETIKETKA_DOP'] = $item["PROP_VALUE"];
                        }
                        if ($item['PROP_IBLOCK_PROPERTY_ID'] == '2170') {
                            $arProp[$item['ID']]['KISLOTNOST'] = $arProps[2170][$item["PROP_VALUE"]];
                        }
                        if ($item['PROP_IBLOCK_PROPERTY_ID'] == '2172') {
                            $arProp[$item['ID']]['PLOTNOST'] = $arProps[2172][$item["PROP_VALUE"]];
                        }
                        if ($item['PROP_IBLOCK_PROPERTY_ID'] == '1767') {
                            $arProp[$item['ID']]['STEPEN_OBZHARKI'] = $arProps[1767][$item["PROP_VALUE"]];
                        }
                        if ($item['PROP_IBLOCK_PROPERTY_ID'] == '1875') {
                            if ($arProps[1875][$item["PROP_VALUE"]]) {
                                $arProp[$item['ID']]['LABEL'] = [];
                                switch (mb_strtolower($arProps[1875][$item["PROP_VALUE"]])) {
                                    case 'новинка':
                                        $arProp[$item['ID']]['LABEL'][] = ['CLASS' => 'green', 'ICON' => 'https://legend-tea.ru/bitrix/templates/b2b/assets/static/img/price_list/green-new.svg', 'TEXT' => 'Новинка'];
                                        break;
                                    case 'хит':
                                        $arProp[$item['ID']]['LABEL'][] = ['CLASS' => 'red', 'ICON' => 'https://legend-tea.ru/bitrix/templates/b2b/assets/static/img/price_list/red-fire.svg', 'TEXT' => 'Хит'];
                                        break;
                                    case 'рекомендуем':
                                        $arProp[$item['ID']]['LABEL'][] = ['CLASS' => 'yellow', 'ICON' => 'https://legend-tea.ru/bitrix/templates/b2b/assets/static/img/price_list/yellow-like.svg', 'TEXT' => 'Советуем'];
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }

                        $this->arData[$idSect]['SECT'][$item['IBLOCK_SECTION_ID']]['PRODUCT'][$item['ID']] = $arProp[$item['ID']];
                    }
                }
            }
        }

        if (!empty($dbItemsTp)) {
            $arProp = [];
            foreach ($dbItemsTp as $item) {
                foreach ($this->arData as $idSect => $sect) {
                    if ($sect['SECT'][$item['IBLOCK_SECTION_ID']]) {

                        if (!isset($this->arData[$idSect]['SECT'][$item['IBLOCK_SECTION_ID']]['PRODUCT'][$item['ELEM_ID']]['TP']) || $this->arData[$idSect]['SECT'][$item['IBLOCK_SECTION_ID']]['PRODUCT'][$item['ELEM_ID']]['TP'][$item['ID']]['ID'] == $item['ID']) {
                            $arProp[$item['ID']]['ID'] = $item["ID"];

                            if ($item['GROUP_ID'] == ID_BASE_PRICE_B2B) $arProp[$item['ID']]['PRICE_1'] = $item["PRICE_VALUE"] . ' ₽';
                            if ($item['GROUP_ID'] == ID_TYPE1_PRICE_B2B) $arProp[$item['ID']]['PRICE_2'] = $item["PRICE_VALUE"] . ' ₽';
                            if ($item['GROUP_ID'] == ID_TYPE2_PRICE_B2B) $arProp[$item['ID']]['PRICE_3'] = $item["PRICE_VALUE"] . ' ₽';
                            if ($item['GROUP_ID'] == ID_TYPE3_PRICE_B2B) $arProp[$item['ID']]['PRICE_4'] = $item["PRICE_VALUE"] . ' ₽';

                            $this->arData[$idSect]['SECT'][$item['IBLOCK_SECTION_ID']]['PRODUCT'][$item['ELEM_ID']]['TP'][$item['ID']] = $arProp[$item['ID']];
                        }
                    }
                }
            }
        }

        foreach ($this->arData as $idMainSect => $sectMain) {
            foreach ($sectMain["SECT"] as $idSect => $sect) {
                $arTp = [];
                $arPr = [];
                foreach ($sect["PRODUCT"] as $key => $item) {
                    if (!empty($item['TP'])) {
                        $arTp[$key] = $item;
                    } else {
                        $arPr[$key] = $item;
                    }
                }
                unset ($this->arData[$idMainSect]['SECT'][$idSect]['PRODUCT']);
                $this->arData[$idMainSect]['SECT'][$idSect]['TP_PRODUCT'] = $arTp;
                $this->arData[$idMainSect]['SECT'][$idSect]['SIMPLE_PRODUCT'] = $arPr;

                if (empty($this->arData[$idMainSect]['SECT'][$idSect]['TP_PRODUCT']) && empty($this->arData[$idMainSect]['SECT'][$idSect]['SIMPLE_PRODUCT'])) {
                    unset($this->arData[$idMainSect]['SECT'][$idSect]);
                }
            }
        }
    }

    public function formatedHTML()
    {
        $html = '<main class="price_list table"  data-pdf="target"  style="background: white">';
        $typeProduct = ['TP_PRODUCT', 'SIMPLE_PRODUCT'];
        foreach ($this->arData as $sectMain) {

            $html .= '<p class="list_title" style="background: white" data-sect="' . $sectMain['CODE'] . '">' . $sectMain['NAME'] . '</p>';

            if ($sectMain['ID'] == 1437 || $sectMain['ID'] == 1445 || $sectMain['ID'] == 1421 || $sectMain['ID'] == 1821) {
                foreach ($sectMain["SECT"] as $sect) {
                    $html .= '
            <div class="table_head">
				<p class="text-lg">' . $sect['NAME'] . '</p>
				<p class="text-md">Цены указаны на 1 000 грамм</p>
			</div>';

                    if ($sectMain['ID'] == 1437) $class = 'table_coffee';
                    else  $class = 'table_tea';

                    $html .= '
            <div class="table ' . $class . '" style="background: white">
			    <div class="thead">
					<ul class="tr"><li>Наименование</li>';

                    if ($sectMain['ID'] == 1437) $html .= '<li>Вкусовой профиль</li>';

                    $html .= '
                        <li>Оптовая цена</li>
						<li>От 15 000 ₽</li>
						<li>От 50 000 ₽</li>
						<li>От 100 000 ₽</li>';

                    $html .= '
					</ul>
				</div>
				<div class="tbody" style="background: white">';
                    foreach ($typeProduct as $type) {
                        foreach ($sect[$type] as $item) {

                            $labelStr = '';
                            if ($item['LABEL']) {
                                foreach ($item['LABEL'] as $label) {
                                    $labelStr .= '<div class="label ' . $label['CLASS'] . '"><img class="icon" src="' . $label['ICON'] . '" alt=""><span>' . $label['TEXT'] . '</span></div>';
                                }
                            }

                            $labels = '<div class="labels"><div class="label big grey-noborder">' . $item['ARTICLE'] . '</div>' . $labelStr . '</div>';

                            $html .= '
<ul class="tr">';
                            $html .= '
                        <li><p class="text-sm">' . $item['NAME'] . '</p>
							<p class="text-caption">' . $item['OPISANIE_ETIKETKA_DOP'] . '</p>
							' . $labels . '
						</li>';

                            if ($sectMain['ID'] == 1437) {
                                $htmlKisl = '';
                                if ($item["KISLOTNOST"]) {
                                    $arStr = explode('/', $item["KISLOTNOST"]);
                                    if ($arStr[0] > 0) $htmlKisl = '<div class="line"><div class="fill" style="width: ' . $arStr[0] . '0%;"></div></div><p class="text-sm-2">кислотность</p>';
                                }

                                $htmlGor = '';
                                if ($item["PLOTNOST"]) {
                                    $arStr = explode('/', $item["PLOTNOST"]);
                                    if ($arStr[0] > 0) $htmlGor = '<div class="line"><div class="fill" style="width: ' . $arStr[0] . '0%;"></div></div><p class="text-sm-2">горечь</p>';
                                }

                                $htmlStepen = '';
                                if ($item["STEPEN_OBZHARKI"]) $htmlStepen = '<p class="text-sm-2">' . $item ["STEPEN_OBZHARKI"] . '</p>';

                                $html .= '<li>' . $htmlKisl . $htmlGor . $htmlStepen . '</li>';
                            }

                            $price = '';
                            if (!empty(['PRICE_1']) && empty($item['TP'])) {
                                $price = $item['PRICE_1'];
                            } elseif (!empty($item['TP']) && count($item['TP']) == 1) {
                                $price = current($item['TP'])['PRICE_1'];
                            } elseif (!empty($item['TP']) && $item['TP']['PRICE_1']) {
                                $price = $item['TP']['PRICE_1'];
                            }
                            $html .= '
<li><p class="text-sm">' . $price . '</p></li>';


                            $price = '';
                            if (!empty(['PRICE_2']) && empty($item['TP'])) {
                                $price = $item['PRICE_2'];
                            } elseif (!empty($item['TP']) && count($item['TP']) == 1) {
                                $price = current($item['TP'])['PRICE_2'];
                            } elseif (!empty($item['TP']) && $item['TP']['PRICE_2']) {
                                $price = $item['TP']['PRICE_2'];
                            }
                            $html .= '
<li><p class="text-sm">' . $price . '</p></li>';


                            $price = '';
                            if (!empty(['PRICE_3']) && empty($item['TP'])) {
                                $price = $item['PRICE_3'];
                            } elseif (!empty($item['TP']) && count($item['TP']) == 1) {
                                $price = current($item['TP'])['PRICE_3'];
                            } elseif (!empty($item['TP']) && $item['TP']['PRICE_3']) {
                                $price = $item['TP']['PRICE_3'];
                            }
                            $html .= '
<li><p class="text-sm">' . $price . '</p></li>';


                            $price = '';
                            if (!empty(['PRICE_4']) && empty($item['TP'])) {
                                $price = $item['PRICE_4'];
                            } elseif (!empty($item['TP']) && count($item['TP']) == 1) {
                                $price = current($item['TP'])['PRICE_4'];
                            } elseif (!empty($item['TP']) && $item['TP']['PRICE_4']) {
                                $price = $item['TP']['PRICE_4'];
                            }
                            $html .= '
<li><p class="text-sm">' . $price . '</p></li>';


                            $html .= '
</ul>';
                        }
                    }
                    $html .= '
                    </div>
                    </div>';
                }
            } else {
                foreach ($sectMain["SECT"] as $sect) {

                    $html .= '
    <div class="table_head">    
            <p class="text-lg">' . $sect['NAME'] . '</p>
    </div>';
                    $html .= '
    <div class="table table_other" style="background: white">
                    <div class="thead">
                        <ul class="tr">
                            <li>Наименование</li>
                            <li>Описание</li> 
                            <li>Цена</li>
                        </ul>
                    </div>';
                    $html .= '
                <div class="tbody" style="background: white">';
                    foreach ($typeProduct as $type) {
                        foreach ($sect[$type] as $item) {
                            $price = '';
                            if (!empty(['PRICE_1']) && empty($item['TP'])) {
                                $price = $item['PRICE_1'];
                            } elseif (!empty($item['TP']) && count($item['TP']) == 1) {
                                $price = current($item['TP'])['PRICE_1'];
                            } elseif (!empty($item['TP']) && $item['TP']['PRICE_1']) {
                                $price = $item['TP']['PRICE_1'];
                            }
                            $labelStr = '';
                            if ($item['LABEL']) {
                                foreach ($item['LABEL'] as $label) {
                                    $labelStr .= '<div class="label ' . $label['CLASS'] . '"><img class="icon" src="' . $label['ICON'] . '" alt=""><span>' . $label['TEXT'] . '</span></div>';
                                }
                            }
                            $labels = '<div class="labels"><div class="label big grey-noborder">' . $item['ARTICLE'] . '</div>' . $labelStr . '</div>';

                            $html .= '
                        <ul class="tr">';
                            $html .= '<li>
                                        <p class="text-sm">' . $item['NAME'] . '</p>
                                      </li>';
                            $html .= '<li>
                                        <p class="text-default">' . $item['OPISANIE_ETIKETKA_DOP'] . '</p>
                                      </li>';
                            $html .= '<li>
                                        <p class="text-sm">' . $price . '</p>
                                      </li>';
                            $html .= '<li>' . $labels . '</li>';
                            $html .= '
                        </ul>';
                        }
                    }
                    $html .= ' 
              </div>
    </div>';

                }
            }
        }
        $html .= '
</main>';
        $this->html = $html;


        $this->script = <<<EOD
    <script>
    function splitPages() {
            let main = document.querySelector('main'),
                heightVar = 1279;
    
            let pageHeight = heightVar - 100
    
            let all = main.querySelectorAll('main>*'),
                titlesArr = [],
                sectionsArr = []
    
            all.forEach((item, index) => {item.classList.contains('list_title') && titlesArr.push(index)})
    
            for (let i = 0; i < titlesArr.length; i++)  sectionsArr[i] = []
            
            for (let i = 0; i < all.length; i++) {
                for (let t = 0; t < titlesArr.length; t++) {
                    if (
                        (t >= 0 && i >= titlesArr[t] && i < titlesArr[t + 1]) ||
                        (t == titlesArr.length - 1 && i >= titlesArr[t])
                    ) {
                        sectionsArr[t].push(all[i])
                    }
                }
            }
    
            sectionsArr.forEach(innerArr => {
                let oldParent = innerArr[0].parentNode,
                    parent = document.createElement('div')
                parent.classList.add('table_holder')
                parent.setAttribute('data-sect', innerArr[0].getAttribute('data-sect'))
    
                oldParent.replaceChild(parent, innerArr[0])
    
                for (let i = 0; i < innerArr.length; i++) {
                    parent.appendChild(innerArr[i])
                }
            })
    
            const recursive = (parent, sectId) => {
                let elements = parent.children
                for (let index = 0; index < elements.length; index++) {
                    let el = Array.from(elements)[index]
                    let top = el.getBoundingClientRect().top - parent.getBoundingClientRect().top,
                        height = el.clientHeight
    
                    if (top + height > pageHeight) {
                        if (el.classList.contains('table_head')) {
                            parent.insertAdjacentHTML('afterend', 
                                '<div class="table_holder" data-sect="' + sectId + '"></div>'
                            );
                            
                            Array.from(elements).forEach((x, ind) => {
                                ind >= index && parent.nextElementSibling.appendChild(x)
                            })
    
                            recursive(parent.nextElementSibling, sectId)
    
                        } else if (el.classList.contains('table_tea') ||
                            el.classList.contains('table_coffee') ||
                            el.classList.contains('table_other')
                        ) {
                            let trs = el.querySelectorAll('.tr')
    
                            for (let trIndex = 0; trIndex < trs.length; trIndex++) {
                                let tr = trs[trIndex]
                                let trTop = tr.getBoundingClientRect().top - parent.getBoundingClientRect().top,
                                    trHeight = tr.clientHeight
    
                                if (trTop + trHeight > pageHeight) {
                                    if (tr.parentNode.classList.contains('thead')) {
                                        parent.insertAdjacentHTML('afterend', 
                                            '<div class="table_holder" data-sect="' + sectId + '"></div>'
                                        );
    
                                        Array.from(elements).forEach((x, ind) => {
                                            ind >= index - 1 && parent.nextElementSibling.appendChild(x)
                                        })
    
                                        recursive(parent.nextElementSibling, sectId)
                                    }
                                    
                                    if (tr.parentNode.classList.contains('tbody')) {
                                        parent.insertAdjacentHTML('afterend', 
                                            '<div class="table_holder" data-sect="' + sectId + '"></div>'
                                        );
                                        
                                        if (tr == tr.parentNode.children[0]) {
                                            Array.from(elements).forEach((x, ind) => {
                                                ind >= index - 1 && parent.nextElementSibling.appendChild(x)
                                            })
    
                                            recursive(parent.nextElementSibling, sectId)
                                        } else if (tr != tr.parentNode.children[0])  {
                                            let rows = tr.parentNode.querySelectorAll('.tr')
    
                                            let type = el.classList.contains('table_coffee') ? 'table_coffee' :
                                                el.classList.contains('table_tea') ? 'table_tea' :
                                                el.classList.contains('table_other') ? 'table_other' : ''
    
                                            let content = type == 'table_coffee' ?
                                                    `<li>Наименование</li>
                                                    <li>Вкусовой профиль</li>
                                                    <li>Оптовая цена</li>
                                                    <li>От 15 000 ₽</li>
                                                    <li>От 50 000 ₽</li>
                                                    <li>От 100 000 ₽</li>` :
                                                type == 'table_tea' ?
                                                    `<li>Наименование</li>
                                                    <li>Оптовая цена</li>
                                                    <li>От 15 000 ₽</li>
                                                    <li>От 50 000 ₽</li>
                                                    <li>От 100 000 ₽</li>` :
                                                type == 'table_other' ?
                                                    `<li>Наименование</li>
                                                    <li>Описание</li>
                                                    <li>Цена</li>` : ''
    
                                            parent.nextElementSibling.insertAdjacentHTML('afterbegin', 
                                                '<div class="table ' + type + '">' + '<div class="thead"><ul class="tr">' + content + '</ul></div><div class="tbody"></div></div>'
                                            );
    
                                            for (let r = trIndex - 1; r < rows.length; r++) parent.nextElementSibling.querySelector('.tbody').appendChild(rows[r])
                                            
    
                                            Array.from(elements).forEach((x, ind) => {
                                                ind > index && parent.nextElementSibling.appendChild(x)
                                            })
                                            recursive(parent.nextElementSibling, sectId)
                                        }
                                    }
                                    break
                                }
                            }
                        }
                        break
                    }
                }
            }
            
    
            for (let tableIndex = 0; tableIndex < document.querySelectorAll('.table_holder').length; tableIndex++) {
                let tableHolder = document.querySelectorAll('.table_holder')[tableIndex]
                if (tableHolder.clientHeight >= 1280) recursive(tableHolder, tableHolder.getAttribute('data-sect'))              
            }
           
            document.querySelectorAll('.table_holder').forEach(x => { x.innerHTML === '' && x.remove() })
            document.querySelectorAll('.table_holder').forEach((x, index) => {
                x.style.backgroundColor = '#fff'
                x.style.display = 'none'
            })
            
            const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms))
            const generateLoop = async () => {
                let holders = document.querySelectorAll(`.table_holder`)
                for (let k = 0; k < holders.length; k++) {
                    let sect = holders[k];
                    let sectId = sect.getAttribute('data-sect')

                    let trs = holders[k].querySelectorAll('.table .tbody .tr')
                    trs.forEach(tr => {
                        let textItem = tr.querySelector('li .text-caption')

                        if (textItem && textItem.innerText != '') {
                            let newText = textItem.innerHTML.replace(/\-/g, '-\u2060')
                            let newText1 = newText.replace(/\\n/g, ' ');
                            textItem.innerText = newText1
                        }
                    })
                    
                    
                    sect.style.display = 'block'
                    await wait(250)
                    fileGenerate(k, sectId)
                    await wait(250)
                    sect.style.display = 'none'
                }
            }
            generateLoop()
        };
    function fileGenerate(k, sectId) {
            let jspdf = new jsPDF({
                unit: 'px',
                orientation: 'landscape',
                format: [960, 640],
                compress: true,
            });
          //  jspdf.setFillColor('White');
            jspdf.internal.scaleFactor = 2;
            let options = {pagesplit: true};
            
            jspdf.addHTML(document.querySelectorAll('.table_holder')[k], options, () => {
                let pdf = jspdf.output('blob')
                const file = new File(
                    [pdf],
                    'page' + String(k).padStart(document.querySelectorAll('.table_holder').length.toString().length, '0') + '.pdf',
                    {type: 'application/pdf'}
                );
                
                let data = new FormData();
                data.append('file', file);
                data.append('section', sectId);
                if(k == document.querySelectorAll('.table_holder').length - 1)  data.append('last_page','Y');
             
                fetch('https://legend-tea.ru/ajax/upload-price-list.php', {
                    method: 'POST',
                    body: data,
                }).then(response => response.text()).then(result => {  console.log('Success:', result);}).catch(error => { console.error('Error:', error); });
            });
        }
    document.addEventListener('DOMContentLoaded', () => {        
        splitPages();
    });
    </script>
    EOD;
    }

    public
    function createHTML($nameFile = 'test', $path = '')
    {
        $html = <<<EOD
<!DOCTYPE html>
<html lang="ru" style="background: white">
<head style="background: white">
<meta charset="UTF-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<style>{$this->style}</style>
<title>Helloworld</title>
</head>
	<body style="background: white">
        {$this->html}
	</body>
{$this->script}
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.0.272/jspdf.debug.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.js"></script>
</html>
EOD;

        if (empty($path)) $path = $this->path;

        $htmlPath = $path . $nameFile . ".html";
        file_put_contents($htmlPath, $html);
        $htmlFile = file_get_contents($htmlPath);

        //rmdir($this->pathAllSect);
    }

}