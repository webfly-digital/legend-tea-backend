<?php namespace Webfly\Generate;

use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\PriceTable;
use Spipu\Html2Pdf\Locale;


class GeneratePhotoProduct
{

    protected $arData = [];

    protected $html = '';
    protected $style = '';
    protected $script = '';
    public $path = '';

    protected $folder = '/bitrix/php_interface/lib/Webfly/Generate';
    protected $pathDownload = '/upload/generate_picture';
    protected $pathQr = '';
    protected $pathBar = '';


    function __construct($root = '')
    {
        if (!empty($root)) $_SERVER['DOCUMENT_ROOT'] = $root;

        \Bitrix\Main\Loader::IncludeModule("catalog");
        \Bitrix\Main\Loader::IncludeModule("iblock");


        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/lib/src/phpqrcode/qrlib.php';

        $this->pathQr = $this->pathDownload . '/qr_pixel/';
        $this->pathBar = $this->pathDownload . '/bar_code/';


        $this->style = "*, *::after, *::before {
  margin: 0;
  padding: 0;
  border: none;
  outline: none;
  box-sizing: border-box;
}

body {
  max-width: 853.3px;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
body.noscroll {
  overflow: hidden;
}

a {
  text-decoration: none;
  color: inherit;
}
a:link, a:visited, a:hover {
  text-decoration: none;
}

aside, nav, footer, header, section, main {
  display: block;
}

ul, ul li {
  list-style: none;
}

input:focus, input:active,
button:focus, button:active {
  outline: none;
}

label {
  cursor: pointer;
}

input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
  -webkit-appearance: none;
}

:root {
  --container-width: 1280px;
  --container-padding: 20px;
  --font-size-html: 16px;
  --font-size-base: 8px;
  --text-color-default: #000000;
}

.container {
  max-width: calc(var(--container-width) + var(--container-padding) * 2);
  margin-inline: auto;
  padding-inline: var(--container-padding);
}

html {
  scroll-behavior: smooth;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  font-size: var(--font-size-html);
  color: var(--text-color-default);
}

body {
  font-size: var(--font-size-base);
  min-height: 100vh;
}

.visually-hidden {
  position: absolute !important;
  width: 1px !important;
  height: 1px !important;
  margin: -1px !important;
  border: 0 !important;
  padding: 0 !important;
  white-space: nowrap !important;
  clip-path: inset(100%) !important;
  clip: rect(0 0 0 0) !important;
  overflow: hidden !important;
}

@media (max-width: 767px) {
  .hidden-mobile {
    display: none !important;
  }
}

@media (min-width: 768px) {
  .visible-mobile {
    display: none !important;
  }
}
        ";
    }

    public
    function execute()
    {
        $this->deleteFiles($_SERVER['DOCUMENT_ROOT'] . $this->pathQr);
        $this->deleteFiles($_SERVER['DOCUMENT_ROOT'] . $this->pathBar);

        $this->generateHTML();
    }


    public
    function saveImageInProduct($request, $file)
    {
        if (empty($request['id']) && empty($file['file'])) return ['result' => 'ok', 'status' => 'нет ид товара и картнки'];

        $productId = $request['id'];
        $oldFileId = $request['old_file'];
        $newWidth = $request['new_width'];
        $newHeight = $request['new_height'];

        $imagick = new \Imagick();
        $imagick->readImage($file['file']['tmp_name']);

        $newImg = $imagick->writeImage($_SERVER['DOCUMENT_ROOT'] . $this->pathDownload . '/picture_product.png');

        if ($newImg) {
            $newFile = [
                'type' => 'image/png',
                'MODULE_ID' => 'iblock',
                'name' => 'product_' . $productId . '_' . time() . '.png',
                'tmp_name' => $_SERVER['DOCUMENT_ROOT'] . $this->pathDownload . '/picture_product.png',
            ];
            $fileID = \CFile::SaveFile($newFile, "iblock");
            if ($fileID > 0) {
                $fileArr = \CFile::MakeFileArray($fileID);
                $arFields['DETAIL_PICTURE'] = $fileArr;

                //  $fileArrOld = \CFile::MakeFileArray($oldFileId);
                // $arFields['DETAIL_PICTURE'] = ['del' => 'Y'];


                $el = new \CIBlockElement;
                $res = $el->Update($productId, $arFields, false, false, false, false);

                if ($res && $oldFileId) \CFile::Delete($oldFileId);
            }
        }

        return ['result' => 'ok', 'status' => 'success'];
    }


    function generateHTML()
    {
        $this->getInfo();
        $this->createHTML();
    }


    public function getInfo()
    {

        $arFilter = array('IBLOCK_ID' => CATALOG_IBLOCK_ID, 'SECTION_ID' => COFFEE_SECTION_ID, 'CHECK_PERMISSIONS' => 'N', /* 'GLOBAL_ACTIVE' => 'Y',*/ );
        $db_list = \CIBlockSection::GetList([], $arFilter, false);
        while ($ar_result = $db_list->GetNext()) {
            $arSect[] = $ar_result['ID'];
        }


        $arProp = [1777, 2087, 1751, 1774, 2086, 1760, 1767, 1769, 1750, 2162];

        $dbItems = \Bitrix\Iblock\ElementTable::getList(array(
            'order' => ['NAME' => 'asc'],
            'select' => array('NAME', 'ID', 'CODE', 'IBLOCK_SECTION_ID',
                'PROP_POMOL_' => 'PROP_POMOL',
                'PROP_LINK_' => 'PROP_LINK',
                'PROP_UPAK_' => 'PROP_UPAK',
                'TP_PROPS_' => 'TP_PROPS',
                'ELEMENT_' => 'ELEMENT',
                'ELEMENT_PROPS_' => 'ELEMENT_PROPS',

                'DETAIL_PAGE_URL_ROW' => 'ELEMENT.IBLOCK.DETAIL_PAGE_URL',
                'ELEMENT.NAME', 'ELEMENT.ID', 'ELEMENT.CODE', 'ELEMENT.IBLOCK_SECTION_ID',
                'ELEMENT.DETAIL_PICTURE'),

            'filter' => array(
                'IBLOCK_ID' => 94,
                'ACTIVE' => 'Y',
                'ELEMENT.ACTIVE' => 'Y',
                'ELEMENT.IBLOCK_SECTION_ID' => $arSect,

                'PROP_LINK.IBLOCK_PROPERTY_ID' => [1785],

                'PROP_POMOL.IBLOCK_PROPERTY_ID' => 1795,
                'PROP_POMOL.VALUE' => 5662,

                'PROP_UPAK.IBLOCK_PROPERTY_ID' => 1812,
                'PROP_UPAK.VALUE' => 5856,

                'TP_PROPS.IBLOCK_PROPERTY_ID' => 1786,

                'ELEMENT_PROPS.IBLOCK_PROPERTY_ID' => $arProp
            ),
            'runtime' => array(
                new \Bitrix\Main\Entity\ReferenceField(
                    'ELEMENT',
                    '\Bitrix\Iblock\ElementTable',
                    ['=this.PROP_LINK.VALUE' => 'ref.ID',]
                ),
                new \Bitrix\Main\Entity\ReferenceField(
                    'ELEMENT_PROPS',
                    '\Bitrix\Iblock\ElementPropertyTable',
                    ['=this.PROP_LINK.VALUE' => 'ref.IBLOCK_ELEMENT_ID',]
                ),

                new \Bitrix\Main\Entity\ReferenceField(
                    'PROP_LINK',
                    '\Bitrix\Iblock\ElementPropertyTable',
                    ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID',]
                ),
                new \Bitrix\Main\Entity\ReferenceField(
                    'PROP_POMOL',
                    '\Bitrix\Iblock\ElementPropertyTable',
                    ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID',]
                ),
                new \Bitrix\Main\Entity\ReferenceField(
                    'PROP_UPAK',
                    '\Bitrix\Iblock\ElementPropertyTable',
                    ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID',]
                ),
                new \Bitrix\Main\Entity\ReferenceField(
                    'TP_PROPS',
                    '\Bitrix\Iblock\ElementPropertyTable',
                    ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID',]
                ),


            ),
        ))->fetchAll();


        $dbProp = \Bitrix\Iblock\PropertyEnumTable::getList(array(
            'order' => ['ID' => 'asc'],
            'select' => ['ID', 'PROPERTY_ID', 'VALUE'],
            'filter' => ['PROPERTY_ID' => array_merge($arProp, [1795, 1812, 1786])]

        ))->fetchAll();

        $arProps = [];
        foreach ($dbProp as $prop) {
            $item[$prop['ID']] = $prop['VALUE'];
            $arProps[$prop['PROPERTY_ID']][$prop['ID']] = $item[$prop['ID']];
        }

        foreach ($dbItems as $item) {
            if (empty($this->arData[$item['ID']]['PROP'])) {
                $item['PROP'] = [];
                $this->arData[$item['ID']] = $item;
            }
            if ($arProps[$item['ELEMENT_PROPS_IBLOCK_PROPERTY_ID']]) {
                $this->arData[$item['ID']]['PROP'] = $this->arData[$item['ID']]['PROP'] + [$item['ELEMENT_PROPS_IBLOCK_PROPERTY_ID'] => $arProps[$item['ELEMENT_PROPS_IBLOCK_PROPERTY_ID']][$item['ELEMENT_PROPS_VALUE']]];
            } else
                $this->arData[$item['ID']]['PROP'] = $this->arData[$item['ID']]['PROP'] + [$item['ELEMENT_PROPS_IBLOCK_PROPERTY_ID'] => $item['ELEMENT_PROPS_VALUE']];


            if ($arProps[$item['PROP_POMOL_IBLOCK_PROPERTY_ID']]) $this->arData[$item['ID']]['PROP'] = $this->arData[$item['ID']]['PROP'] + [$item['PROP_POMOL_IBLOCK_PROPERTY_ID'] => $arProps[$item['PROP_POMOL_IBLOCK_PROPERTY_ID']][$item['PROP_POMOL_VALUE_ENUM']]];
            else  $this->arData[$item['ID']]['PROP'] = $this->arData[$item['ID']]['PROP'] + [$item['PROP_POMOL_IBLOCK_PROPERTY_ID'] => $item['PROP_POMOL_VALUE_ENUM']];

            if ($arProps[$item['PROP_UPAK_IBLOCK_PROPERTY_ID']]) $this->arData[$item['ID']]['PROP'] = $this->arData[$item['ID']]['PROP'] + [$item['PROP_UPAK_IBLOCK_PROPERTY_ID'] => $arProps[$item['PROP_UPAK_IBLOCK_PROPERTY_ID']][$item['PROP_UPAK_VALUE_ENUM']]];
            else   $this->arData[$item['ID']]['PROP'] = $this->arData[$item['ID']]['PROP'] + [$item['PROP_UPAK_IBLOCK_PROPERTY_ID'] => $item['PROP_UPAK_VALUE_ENUM']];

            if ($arProps[$item['TP_PROPS_IBLOCK_PROPERTY_ID']]) $this->arData[$item['ID']]['PROP'] = $this->arData[$item['ID']]['PROP'] + [$item['TP_PROPS_IBLOCK_PROPERTY_ID'] => $arProps[$item['TP_PROPS_IBLOCK_PROPERTY_ID']][$item['TP_PROPS_VALUE']]];
            else   $this->arData[$item['ID']]['PROP'] = $this->arData[$item['ID']]['PROP'] + [$item['TP_PROPS_IBLOCK_PROPERTY_ID'] => $item['TP_PROPS_VALUE']];
        }

        foreach ($this->arData as $key => $item) {
            $this->arData[$key]['INFO']['id'] = $item['ELEMENT_ID'];
            $itemUrl = [
                'ID' => $item['ELEMENT_ID'],
                'CODE' => $item['ELEMENT_CODE'],
                'IBLOCK_SECTION_ID' =>  $item['ELEMENT_IBLOCK_SECTION_ID'],
            ];
            $this->arData[$key]['DETAIL_PAGE_URL'] = 'https://' . $_SERVER['SERVER_NAME'] . \CIBlock::ReplaceDetailUrl($item["DETAIL_PAGE_URL_ROW"], $itemUrl, true, "E");

            if ($this->arData[$key]['DETAIL_PAGE_URL']) {
                $pathQr = $this->pathQr . 'qr_pixel_' . $item['ELEMENT_ID'] . '.png';
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . $pathQr, '');

                \QRcode::png($this->arData[$key]['DETAIL_PAGE_URL'], $_SERVER['DOCUMENT_ROOT'] . $pathQr, 'Q', '2px', '1px');
                $this->arData[$key]['INFO']['qr'] = $pathQr;
            }
            $this->arData[$key]['INFO']['old_file'] = $item['ELEMENT_DETAIL_PICTURE'];

            foreach ($item['PROP'] as $keyProp => $prop) {
                $this->arData[$key]['INFO']['size'] = 1000;
                switch ($keyProp) {
                    case 1777:
                        $this->arData[$key]['INFO']['title'] = $prop;
                        break;
                    case 2162:
                        $this->arData[$key]['INFO']['harvest_date'] = $prop;
                        break;
                    case 1751:
                        $this->arData[$key]['INFO']['article_number'] = $prop;
                        break;
                    case 1774:
                        $this->arData[$key]['INFO']['country'] = $prop;
                        break;
                    case 2086:
                        $this->arData[$key]['INFO']['coffee_bean_type'] = $prop;
                        break;
                    case 1760:
                        $this->arData[$key]['INFO']['process_type'] = $prop;
                        break;
                    case 1767:
                        $this->arData[$key]['INFO']['roasting'] = $prop;
                        break;
                    case 1795://tp
                        $this->arData[$key]['INFO']['grind_type'] = $prop;
                        break;
                    case 1786://tp
                        if (strlen($prop) == 13) {
                            if (file_exists('/root/vendor/autoload.php')) require_once '/root/vendor/autoload.php';
                            $renderer = new \Picqer\Barcode\BarcodeGeneratorPNG();
                            $res = $renderer->getBarcode($prop, 'EAN13');
                            $pathBar = $this->pathBar . 'bar_code' . $item['ELEMENT_ID'] . '.png';

                            file_put_contents($_SERVER['DOCUMENT_ROOT'] . $pathBar, $res);
                            $this->arData[$key]['INFO']['code'] = $pathBar;
                            $this->arData[$key]['INFO']['code_number'] = $prop;
                        }
                        break;
                    default:
                        break;

                }
            }
        }

    }


    function deleteFiles($dir)
    {

        // итерация по файлам по одному
        foreach (glob($dir . '/*') as $file) {
            // проверка, является ли элемент файлом, а не подпапкой
            if (is_file($file)) {
                // удаление файла
                unlink($file);
            }
        }
    }

    public
    function createHTML($nameFile = '/pictureProduct', $path = '')
    {
        foreach ($this->arData as $key => $item) {
            $elems[] = $item['INFO'];
        }
        $obj = \CUtil::PhpToJSObject($elems);
        var_dump($obj);

        $this->script = <<<EOD
    <script>
        let allData = {$obj};
        const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms))

        const checkReponse = (res) => {
            return res.ok ? res.json() : res.json().then((err) => Promise.reject(err));
        };
        
        document.addEventListener('DOMContentLoaded', async () => {
            const avenirRegular = new FontFace('Avenir', 'url({$this->folder}/assets/fonts/Avenir.woff2)')
            const avenirDemi = new FontFace('Avenir Demi', 'url({$this->folder}/assets/fonts/AvenirNextCyr-BoldItalic.woff)')
            document.fonts.add(avenirRegular)
            document.fonts.add(avenirDemi)

            avenirRegular.load().then(() => {
                document.body.style.fontFamily = 'Avenir'
            })

            function onload2promise(img){
                return new Promise((resolve, reject) => {
                    img.onload = () => resolve(img);
                    img.onerror = reject;
                });
            }

            let aspRatio, renderHeight, renderSize
            let imageSrc = '{$this->folder}/assets/img/png/montis_mockup_1000g.png'

            let fileName, fileId, oldFile, fileTitle

            let newFigure = document.createElement('figure')
            newFigure.style.position = 'relative'
            newFigure.style.aspectRatio = aspRatio
            newFigure.style.width = '853.3px'

            let newImage = document.createElement('img')
            newImage.style.width = '100%'
            newImage.style.height = '100%'
            newImage.style.display = 'block'
            newImage.src = imageSrc

            let newArticle = document.createElement('article')
            newArticle.style.position = 'absolute'
            newArticle.style.paddingInline = '13.3px'
            newArticle.style.bottom = '124.6px'
            newArticle.style.left = '50.25%'
            newArticle.style.transform = 'translateX(-50%)'
            newArticle.style.width = '338.6px'
            newArticle.style.height = '133.3px'
            newArticle.style.borderRadius = '2.6px'

            let newLine = document.createElement('div')
            newLine.style.position = 'absolute'
            newLine.style.bottom = '115.3px'
            newLine.style.left = '50.25%'
            newLine.style.transform = 'translateX(-50%)'
            newLine.style.width = '338.6px'
            newLine.style.height = '13.3px'
            newLine.style.backgroundColor = '#73B8BD'
            newLine.style.borderRadius = '0 0 2.6px 2.6px'

            let newTitle = document.createElement('p')
            newTitle.style.fontSize = '14.6px'
            newTitle.style.letterSpacing = '2.6px'
            newTitle.style.textTransform = 'uppercase'
            newTitle.style.lineHeight = '1'
            newTitle.style.textAlign = 'center'

            let newSubtitle = document.createElement('p')
            newSubtitle.style.marginTop = '16px'
            newSubtitle.style.fontSize = '9px'
            newSubtitle.style.textAlign = 'center'

            let newQr = document.createElement('img')
            newQr.style.position = 'absolute'
            newQr.style.bottom = '26.6px'
            newQr.style.left = '13.3px'
            newQr.style.width = '40px'
            newQr.style.height = '40px'

            let newInfo = document.createElement('p')
            newInfo.style.fontSize = '9px'
            newInfo.style.position = 'absolute'
            newInfo.style.bottom = '24px'

            let newCode = document.createElement('img')
            newCode.style.position = 'absolute'
            newCode.style.bottom = '26.6px'
            newCode.style.right = '13.3px'
            newCode.style.height = '40px'
            newCode.style.width = '106.6px'

            let newCodeNumber = document.createElement('p')
            newCodeNumber.style.position = 'absolute'
            newCodeNumber.style.bottom = '20px'
            newCodeNumber.style.left = '223px'
            newCodeNumber.style.padding = '0px 2px'
            newCodeNumber.style.fontSize = '12px'
            newCodeNumber.style.lineHeight = '13px'
            newCodeNumber.style.fontWeight = '700'
            newCodeNumber.style.color = '#000000'
            newCodeNumber.style.background = 'linear-gradient(to right, #f6f6f6, #efefef)';

            newArticle.appendChild(newTitle)
            newArticle.appendChild(newSubtitle)
            newArticle.appendChild(newInfo)

            newFigure.appendChild(newImage)
            newFigure.appendChild(newArticle)
            newFigure.appendChild(newLine)

            document.getElementById('generate').appendChild(newFigure)

            const generateRecursion = (callback) => {
                const recursion = async (index) => {
                    if (!allData[index]) {
                        console.log('end')
                        return;
                    }

                    let data = allData[index]
                    console.log(data)

                    // document.getElementById('generate').innerHTML = ""

                    fileName = data.size
                    fileId = data.id
                    oldFile = data.old_file
                    fileTitle = data.title

                    switch (data.size) {
                        case '1000':
                            aspRatio = '1280 / 1934'
                            imageSrc = '{$this->folder}/assets/img/png/montis_mockup_1000g.png'
                            renderHeight = 1289.3
                            renderSize = [853.3, 1289.3]
                            break;
                        case '500':
                            aspRatio = '1280 / 1620'
                            imageSrc = '{$this->folder}/assets/img/png/montis_mockup_500g.png'
                            renderHeight = 1080
                            renderSize = [853.3, 1080]
                            break;
                        default:
                            aspRatio = '1 / 1'
                            imageSrc = '{$this->folder}/assets/img/png/montis_mockup_250g.png'
                            renderHeight = 853.3
                            renderSize = [853.3, 853.3]
                            break;
                    }

                    await wait(250);

                    newTitle.innerHTML = data.title;
                    newSubtitle.innerHTML = 
                        (typeof data.country !== "undefined" ? "Страна: <b style='font-family: 'Avenir Demi';'>" + data.country + "</b>. " : "") + 
                        (typeof data.harvest_date !== "undefined" ? "Урожай: <b style='font-family: 'Avenir Demi';'>" + data.harvest_date + "</b><br>" : "<br>") + 
                        (typeof data.coffee_bean_type !== "undefined" ? data.coffee_bean_type + ". " : "") + 
                        (typeof data.process_type !== "undefined" ? data.process_type + "." : "");
                    
                    newInfo.innerHTML = 
                        (typeof data.roasting !== "undefined" ? "<span style='white-space: nowrap;'>" + data.roasting + "</span><br>" : "") + 
                        (typeof data.grind_type !== "undefined" ? "<span style='white-space: nowrap;'>" + data.grind_type + "</span><br>" : "") + 
                        (typeof data.article_number !== "undefined" ? "<span style='white-space: nowrap;'>Арт.: " + data.article_number + "</span><br>" : "") + 
                        (typeof data.roasting_date !== "undefined" ? "<span style='white-space: nowrap;'>Дата обжарки:" + data.roasting_date + "</span>" : "");
                    
                    if (data.qr && data.qr !== '') {
                        newInfo.style.left = '64px';
                        newQr.src = data.qr;
                        newArticle.appendChild(newQr);
                    } else {
                        newInfo.style.left = '13.3px';
                        newArticle.removeChild(newQr);
                    }

                    if (data.code && data.code !== '') {
                        newCode.src = data.code;
                        newArticle.appendChild(newCode);
                    } else newArticle.removeChild(newCode);

                    if (data.code_number && data.code !== '') {
                        newCodeNumber.innerHTML = data.code_number
                        newArticle.appendChild(newCodeNumber)
                    } else newArticle.removeChild(newCodeNumber);

                    await wait(250);

                    html2canvas(document.getElementById('generate'), {
                        scale: 4,
                    }).then(async(canvas) => {
                        let img = canvas.toDataURL("image/jpeg", 0.9);

                        let jspdf = new jsPDF({
                            unit: 'px',
                            orientation: 'portrait',
                            format: [renderSize[1]*1.2, renderSize[0]*1.2],
                            compress: true
                        });

                        await wait(250)

                        jspdf.addImage(img, 'JPEG', 0, 0, renderSize[0]*1.2, renderSize[1]*1.2)

                        let pdf = jspdf.output('blob')

                        const file = new File(
                            [pdf], "card_" + fileName + '.pdf',
                            {type: 'application/pdf'}
                        );

                        let data = new FormData();
                        data.append('file', file);
                        data.append('id', fileId);
                        data.append('old_file', oldFile);

                        await wait(250)

                        fetch('/ajax/generate/ajax.php', {
                            method: 'POST',
                            body: data,
                        })
                        .then(response => {
                            response.json()
                        })
                        .then(() => {
                            recursion(index + 1)
                        })
                        .then(result => {
                            console.log('Success:', result);
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                    })

                    // let jspdf = new jsPDF({
                    //     unit: 'px',
                    //     orientation: 'portrait',
                    //     format: [renderSize[0]*4, renderSize[1]*4],
                    // });

                    // jspdf.internal.scaleFactor = 0.25;

                    // await wait(500)

                    // jspdf.addHTML(document.getElementById('generate'), {}, async () => {
                    //     let pdf = jspdf.output('blob')

                    //     const file = new File(
                    //         [pdf], "card_" + fileName + '.pdf',
                    //         {type: 'application/pdf'}
                    //     );
                        
                    //     let data = new FormData();
                    //     data.append('file', file);
                    //     data.append('id', fileId);
                    //     data.append('old_file', oldFile);
                        
                    //     await wait(500)

                    //     fetch('/ajax/generate/ajax.php', {
                    //         method: 'POST',
                    //         body: data,
                    //     })
                    //     .then(response => {
                    //         response.json()
                    //     })
                    //     .then(() => {
                    //         recursion(index + 1)
                    //     })
                    //     .then(result => {
                    //         console.log('Success:', result);
                    //     })
                    //     .catch(error => {
                    //         console.error('Error:', error);
                    //     });
                    // });
                }
                recursion(0)
            }

            newImage.onload = generateRecursion

        })
    </script>
    EOD;

        $this->html = '<div id="generate" style="background-color: white"></div>';

        $html = <<<EOD
<!DOCTYPE html>
<html lang="en">
<head style="background: white">

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  @font-face {
            font-family: "Avenir";
            src: url({$this->folder}/assets/fonts/Avenir.woff2) format("truetype");
        }
        @font-face {
            font-family: "Avenir Demi";
            src: url({$this->folder}/assets/fonts/AvenirNextCyr-BoldItalic.woff) format("truetype");
        }
        body {
            font-family: "Avenir", sans-serif;
        }
{$this->style}</style>
<title>Товар</title>

</head>
	<body style="background: white">
        {$this->html}
	</body>
	
    {$this->script}
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.0.272/jspdf.debug.js"></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"
        integrity="sha512-01CJ9/g7e8cUmY0DFTMcUw/ikS799FHiOA0eyHsUWfOetgbx/t6oV4otQ5zXKQyIrQGTHSmRVPIgrgLcZi/WMA=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    ></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.0.272/jspdf.debug.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>    
</html>
EOD;


        $htmlPath = $_SERVER['DOCUMENT_ROOT'] . $this->pathDownload . $nameFile . ".html";
        file_put_contents($htmlPath, $html);
        $htmlFile = file_get_contents($htmlPath);

    }

}


