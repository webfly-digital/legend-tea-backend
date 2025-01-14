<? foreach ($arResult['ELEMENTS'] as $elem): ?>
    <div class="product_card" data-product-card-pdf="<?= $elem["ID"] ?>" id="product_card_pdf"
         data-art="<?= $elem['PROPERTIES']["CML2_ARTICLE"]['~VALUE'] ?>"
         style="background: white;font-size:19.2px;width: 1920px; display: none">
        <div class="left">
            <? if ($elem['LOGO']): ?>
                <img class="logo" src="<?= $elem['LOGO'] ?>" alt="">
            <? else: ?>
                <img class="logo" src="<?= SITE_TEMPLATE_PATH ?>/assets/static/img/big_logo.png" alt="">
            <? endif ?>
            <div class="labels">
                <? if ($elem['LABEL']['ICON']): ?>
                    <div class="label big <?= $elem['LABEL']['CLASS'] ?>">
                        <img src="<?= SITE_TEMPLATE_PATH ?>/assets/static/img/icon/<?= $elem['LABEL']['ICON'] ?>.svg">
                        <span><?= $elem['LABEL']['TEXT'] ?></span>
                    </div>
                <? endif ?>
                <? if (!empty($elem['PROPERTIES']["CML2_ARTICLE"]['~VALUE'])): ?>
                    <div class="label big grey-noborder">
                        <span><?= $elem['PROPERTIES']["CML2_ARTICLE"]['~VALUE'] ?></span>
                    </div>
                <? endif; ?>
            </div>
            <div class="title"><?= $elem["NAME"] ?></div>
            <? if (!empty($elem['PROPERTIES']["OPISANIE_ETIKETKA_DOP"]['VALUE'])): ?>
                <div class="subtitle">
                    <?= $elem['PROPERTIES']["OPISANIE_ETIKETKA_DOP"]['VALUE'] ?>
                </div>
            <? endif; ?>
            <? if ($elem['TYPE'] == 'TEA'): ?>
                <? if (!empty($elem['PROPERTIES']["VREMYA_ZAVARIVANIYA"]['VALUE']) || !empty($elem['PROPERTIES']["TEMPERATURA_ZAVARIVANIYA"]['VALUE']) || !empty($elem['PROPERTIES']["VES_CHAYA"]['VALUE'])): ?>
                    <div class="filled">
                        <? if (!empty($elem['PROPERTIES']["VREMYA_ZAVARIVANIYA"]['VALUE'])): ?>
                            <div>
                                <img src="<?= SITE_TEMPLATE_PATH ?>/assets/static/img/icon/card-time.svg" alt="">
                                <p>Время заваривания:
                                    <br> <?= $elem['PROPERTIES']["VREMYA_ZAVARIVANIYA"]['VALUE'] ?>
                                </p>
                            </div>
                        <? endif; ?>
                        <? if (!empty($elem['PROPERTIES']["TEMPERATURA_ZAVARIVANIYA"]['VALUE'])): ?>
                            <div>
                                <img src="<?= SITE_TEMPLATE_PATH ?>/assets/static/img/icon/card-temperature.svg" alt="">
                                <p>Температура:
                                    <br> <?= $elem['PROPERTIES']["TEMPERATURA_ZAVARIVANIYA"]['VALUE'] ?></p>
                            </div>
                        <? endif; ?>
                        <? if (!empty($elem['PROPERTIES']["VES_CHAYA"]['VALUE'])): ?>
                            <div>
                                <img src="<?= SITE_TEMPLATE_PATH ?>/assets/static/img/icon/card-weight.svg" alt="">
                                <p>Вес чая: <br> <?= $elem['PROPERTIES']["VES_CHAYA"]['VALUE'] ?></p>
                            </div>
                        <? endif; ?>
                    </div>
                <? endif; ?>
            <? endif; ?>
            <ul>
                <li class="text-md">
                    <dl>
                        <? if ($elem['TYPE'] == 'COFFFEE'): ?>
                            <? if (!empty($elem['PROPERTIES']["VID_KOFE"]['VALUE'])): ?>
                                <dt>Вид:</dt>
                                <dd><?= $elem['PROPERTIES']["VID_KOFE"]['VALUE'] ?></dd>
                            <? endif; ?>
                        <? endif; ?>
                        <? if ($elem['TYPE'] == 'TEA'): ?>
                            <? if (!empty($elem['PROPERTIES']["VID_CHAYA"]['VALUE'])): ?>
                                <dt>Вид:</dt>
                                <dd><?= $elem['PROPERTIES']["VID_CHAYA"]['VALUE'] ?></dd>
                            <? endif; ?>
                            <? if (!empty($elem['PROPERTIES']["KATEGORIYA_CHAYA"]['VALUE'])): ?>
                                <dt>Категория:</dt>
                                <dd><?= $elem['PROPERTIES']["KATEGORIYA_CHAYA"]['VALUE'] ?></dd>
                            <? endif; ?>
                        <? endif; ?>
                        <? if (!empty($elem['PROPERTIES']["OSNOVA"]['VALUE'])): ?>
                            <dt>Страна происхождения:</dt>
                            <dd><?= $elem['PROPERTIES']["OSNOVA"]['VALUE'] ?></dd>
                        <? endif; ?>
                        <? if ($elem['TYPE'] == 'COFFFEE'): ?>
                            <? if (!empty($elem['PROPERTIES']["STEPEN_OBZHARKI"]['VALUE'])): ?>
                                <dt>Степень обжарки:</dt>
                                <dd><?= $elem['PROPERTIES']["STEPEN_OBZHARKI"]['VALUE'] ?></dd>
                            <? endif; ?>
                        <? endif; ?>

                    </dl>
                </li>
                <li class="text-md">
                    <div class="progresses">
                        <? if ($elem['TYPE'] == 'TEA') {
                            $propScale = [
                                'SKOROST_ZAVARIVANIYA' => ['Медленно', 'Средне', 'Быстро', 'Очень быстро'],
                                'KREPOST' => ['Легкий', 'Умеренный', 'Крепкий', 'Очень крепкий'],
                                'AROMAT' => ['Почти отсутствует', 'Слабый', 'Умеренный', 'Яркий'],
                                'VKUS_1' => ['Очень слабый', 'Слабый', 'Средний', 'Насыщенный'],
                            ];
                            $scaleCount = 4;
                        } else if ($elem['TYPE'] == 'COFFFEE') {
                            $propScale = [
                                'KISLOTNOST' => 'KISLOTNOST',
                                'PLOTNOST' => 'PLOTNOST'
                            ];
                            $scaleCount = 10;
                        }
                        ?>

                        <? foreach ($propScale as $type => $propValues) : ?>
                            <? if (!empty($elem['PROPERTIES'][$type]['VALUE'])):
                                if ($elem['TYPE'] == 'TEA') {
                                    $value = array_search($elem['PROPERTIES'][$type]['VALUE'], $propValues);
                                } else if ($elem['TYPE'] == 'COFFFEE') {
                                    $arStr = explode('/', $elem['PROPERTIES'][$type]['VALUE']);
                                    if (count($arStr) == 2) $value = $arStr[0];
                                }
                                ?>
                                <? if (isset($value)): ?>
                                <div class="progress-item">
                                    <p><?= $elem['PROPERTIES'][$type]['NAME'] ?>:</p>
                                    <ul>
                                        <? for ($i = 0; $i < $scaleCount; $i++) : ?>
                                            <li class="<?= $i <= $value ? "active" : "" ?>"></li>
                                        <? endfor; ?>
                                    </ul>
                                    <? if ($elem['TYPE'] == 'TEA'): ?>
                                        <span><?= $elem['PROPERTIES'][$type]['VALUE'] ?></span>
                                    <? endif; ?>
                                </div>
                            <? endif; ?>
                            <? endif; ?>
                        <? endforeach; ?>
                    </div>
                </li>
            </ul>
        </div>
        <dl class="right">
            <? if (!empty($elem['PROPERTIES']["MARKEYTINGOVOE_OPISANIE"]['~VALUE'])): ?>
                <div> <?= $elem['PROPERTIES']["MARKEYTINGOVOE_OPISANIE"]['~VALUE'] ?></div>
            <? endif; ?>
        </dl>
    </div>
<? endforeach; ?>

<script>
    function fileGenerate() {
        const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms))
        let cardPdf = document.querySelectorAll('[data-product-card-pdf]');

        if (cardPdf) {
            const generateLoop = async () => {
                for (let i = 0; i < cardPdf.length; i++) {
                    let card = cardPdf[i]
                    card.style.display = ''
                    await wait(100)

                    let jspdf = new jsPDF({
                        unit: "px",
                        orientation: "landscape",
                        format: [960, 640],
                        compress: true,
                    });
                    //    jspdf.setFillColor("White");
                    //   jspdf.internal.scaleFactor = 2;

                    let nameFile = card.hasAttribute('data-art') ? card.getAttribute('data-art') : 'product';
                    jspdf.addHTML(card, async function () {
                        jspdf.save(nameFile);
                        setTimeout(() => (card.remove()), 100);
                    });
                    await wait(100)
                }
            }
            generateLoop();
        }
    }

    fileGenerate();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.0.272/jspdf.debug.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.js"></script>