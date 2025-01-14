<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetPageProperty("description", "У нас вы можете купить кофе или чай оптом — мы предлагаем нашим клиентам продукцию самого высокого качества. Осуществляем доставку заказов по всей России. Заказывайте по ☎️: 8 800 700-78-87.");
$APPLICATION->SetPageProperty("HIDE_LEFT_BLOCK", "Y");
$APPLICATION->SetPageProperty("title", "Оптовые поставки | Чайно-кофейная компания «Легенда Чая»");
$APPLICATION->SetTitle("Оптовые поставки");

use Bitrix\Main\Page\Asset;

Asset::getInstance()->addCss("https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css");
Asset::getInstance()->addJs("https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js");

?>
    <div class="big-image">
        <div class="imgholder">
            <img src="/bitrix/templates/aspro_max/img/bg-big.png" alt="">
        </div>
        <div class="big-image-content">
            <p class="h1 white">
                Оптовые поставки кофе и чая собственного производства и с собственного склада
            </p>
            <div id="btnPrice">
                <script data-b24-form="click/27/vgtvz9" data-skip-moving="true">(function (w, d, u) {
                        var s = d.createElement('script');
                        s.async = true;
                        s.src = u + '?' + (Date.now() / 180000 | 0);
                        var h = d.getElementsByTagName('script')[0];
                        h.parentNode.insertBefore(s, h);
                    })(window, document, 'https://crm.legend-tea.ru/upload/crm/form/loader_27_vgtvz9.js');</script>
                <a class="btn  btn-transparent-border-color red  animate-load has-ripple">ПОЛУЧИТЬ ОПТОВЫЙ ПРАЙС</a>
            </div>
        </div>
    </div>
    <script>
        let observer = new MutationObserver(mutationRecords => {
            if (mutationRecords[0].attributeName == 'class' && mutationRecords[0].target.classList.contains('loadings')) {
                mutationRecords[0].target.classList.remove('loadings')
            }
        });
        observer.observe(BX('btnPrice'), {
            attributes: true,
        });
    </script>
    <div class="height-block">
        <section class="maxwidth-theme">
            <div class="meta-items">
                <div class="meta-item">
                    <img src="/bitrix/templates/aspro_max/img/1.png" alt="">
                    <div class="meta-item-info">
                        <p class="suptitle">
                            Вендинг
                        </p>
                        <p class="h2">
                            Вендинговым компаниям
                        </p>
                        <ul>
                            <li>Всегда свежий кофе</li>
                            <li>Лучшее соотношение цены и качества</li>
                            <li>Великолепные бленды</li>
                        </ul>
                    </div>
                </div>
                <div class="meta-item">
                    <img src="/bitrix/templates/aspro_max/img/2.png" alt="">
                    <div class="meta-item-info">
                        <p class="suptitle">
                            HORECA
                        </p>
                        <p class="h2">
                            Ресторанам, кафе, отелям
                        </p>
                        <ul>
                            <li>Огромный выбор кофе под любого клиента кофейни</li>
                            <li>Широкая библиотека чайных купажей</li>
                        </ul>
                    </div>
                </div>
                <div class="meta-item">
                    <img src="/bitrix/templates/aspro_max/img/3.png" alt="">
                    <div class="meta-item-info">
                        <p class="suptitle">
                            RETAIL
                        </p>
                        <p class="h2">
                            Кофейно-чайным магазинам
                        </p>
                        <ul>
                            <li>Большой ассортимент кофе и чая для продажи в розничных точках</li>
                            <li>Весь товар в наличии</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="height-block">
        <section class="maxwidth-theme">
            <div class="banner-img">
                <img src="/bitrix/templates/aspro_max/img/banner.png" alt="">
                <div class="banner-img-content">
                    <div class="h1">
                        Откуда наша продукция?
                    </div>
                    <ul>
                        <li>
                            <div class="h2 coffee">
                                Кофе
                            </div>
                            <p class="subtitle">
                                Мы выбираем только лучших поставщиков кофе, которые работают напрямую с фермерами и
                                экспортерами.
                            </p>
                        </li>
                        <li>
                            <div class="h2 tea">
                                Чай
                            </div>
                            <p class="subtitle">
                                Мы сотрудничаем с поставщиками чая по всему миру. В нашем ассортименте есть чаи из
                                Африки, Китая, Индии, Японии, Шри-Ланки, Парагвая, лечебные травяные сборы с гор Кавказа
                                и др. Мы производим чайные купажи из сырья премиального класса.
                            </p>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
    <div class="height-block-big">
        <section class="maxwidth-theme">
            <ul class="list-items">
                <li><img src="/bitrix/templates/aspro_max/img/list-1.svg" alt="">
                    <p class="body">
                        Согласование и запуск СТМ за 3 дня
                    </p>
                </li>
                <li><img src="/bitrix/templates/aspro_max/img/list-2.svg" alt="">
                    <p class="body">
                        Готовые варианты ассортимента товаров для магазинов 15 кв.метров
                    </p>
                </li>
                <li><img src="/bitrix/templates/aspro_max/img/list-3.svg" alt="">
                    <p class="body">
                        Разработка купажей по вашему образцу
                    </p>
                </li>
                <li><img src="/bitrix/templates/aspro_max/img/list-4.svg" alt="">
                    <p class="body">
                        Оформи и подтверди заказ до 13:00 и мы отправим заказ сегодня
                    </p>
                </li>
                <li><img src="/bitrix/templates/aspro_max/img/list-5.svg" alt="">
                    <p class="body">
                        Поставка от производителя
                    </p>
                </li>
            </ul>
        </section>
    </div>
    <div class="height-block">
        <section class="maxwidth-theme">
            <div class="meta-items">
                <div class="h1">
                    Контроль качества
                </div>
                <div class="meta-item big">
                    <img src="/bitrix/templates/aspro_max/img/4.png" alt="">
                    <div class="meta-item-info">
                        <p class="h2">
                            Семпл ростер Roest
                        </p>
                        <p class="body">
                            Сэмпл-ростер Roest — самый совершенный сэмпл-ростер в мире из Норвегии с загрузкой до 100
                            грамм кофе. На нем мы обжариваем все образцы из новых поступлений кофе, чтобы определить их
                            качество и вкус перед промышленной обжаркой.
                        </p>
                        <p class="body">
                            Прекрасный сэмпл-ростер. По методу обжарки идентичен ростеру Loring. Прежде чем закупать
                            зеленый кофе, мы обжариваем образцы на этом ростере Roest.
                        </p>
                    </div>
                </div>
                <div class="meta-item big">
                    <div class="swiper">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <img src="/bitrix/templates/aspro_max/img/dark-1.png" alt="">
                            </div>
                            <div class="swiper-slide">
                                <img src="/bitrix/templates/aspro_max/img/dark-2.png" alt="">
                            </div>
                            <div class="swiper-slide">
                                <img src="/bitrix/templates/aspro_max/img/dark-3.png" alt="">
                            </div>
                            <div class="swiper-slide">
                                <img src="/bitrix/templates/aspro_max/img/dark-4.png" alt="">
                            </div>
                        </div>
                        <div class="swiper-pagination">
                        </div>
                    </div>
                    <div class="meta-item-info">
                        <p class="h2">
                            Оборудование
                        </p>
                        <p class="body">
                            При поступлении зеленого зерна на склад, мы проверяем зеленый кофе на плотность и влажность.
                            Это нужно для того чтобы мы получали сырье высокого качества. <br>
                            От обжарки к обжарке мы должны получать ровный цвет обжаренного зерна. В этом нам помогает
                            колориметр lighttels.
                        </p>
                        <p class="body">
                            Кофемолка Sweet Lab способна стабильно выдавать ровный помол с точностью до микрон. А бойлер
                            Marco всегда выдает нужную температуру воды.
                        </p>
                    </div>
                </div>
                <div class="meta-item big">
                    <img src="/bitrix/templates/aspro_max/img/6.png" alt="">
                    <div class="meta-item-info">
                        <p class="h2">
                            Сертификаты SCA и капинги
                        </p>
                        <p class="body">
                            SCA помогает в постановке системных навыков оценки арабики по ряду параметров, входящих в
                            лист оценки. Девятнадцать сложных тестов прокачивают сенсорные навыки, помогают точнее
                            описывать свои ощущения вкусовых профилей сортов кофе.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="height-block dark">
        <section class="maxwidth-theme">
            <div class="list-column-wrapper">
                <div class="h1 white">
                    Технологичное <br>
                    производство
                </div>
                <ul class="list-column">
                    <li><img src="/bitrix/templates/aspro_max/img/dark-1.png" alt="">
                        <div class="list-column-item-content">
                            <p class="small">
                                01/04
                            </p>
                            <p class="h2">
                                Обжарка
                            </p>
                            <p class="subtitle">
                                Ростер Loring – это один из лидеров на мировом рынке профессионального оборудования для
                                обжарки кофе. <br>
                                С ростером Loring S15 Falcon мы расширяем границы сотрудничества с компаниями сегмента
                                HoReCa. Ростер Loring S15 Falcon позволит нам обжаривать небольшие партии кофе: как для
                                эспрессо, так и для фильтра. Тем самым, создавая неповторимые и уникальные купажи для
                                тех, кому важно предлагать своим гостям только лучшее.
                            </p>
                        </div>
                    </li>
                    <li><img src="/bitrix/templates/aspro_max/img/dark-2.png" alt="">
                        <div class="list-column-item-content">
                            <p class="small">
                                02/04
                            </p>
                            <p class="h2">
                                Фотосепаратор
                            </p>
                            <p class="subtitle">
                                Колорсортер необходим для фильтрации качественного зерна от бракованного. С помощью
                                фотосепаратора Сапсан мы убираем квакеры из общей массы обжаренного зерна, тем самым
                                улучшая вкусовые характеристики кофе.
                            </p>
                        </div>
                    </li>
                    <li><img src="/bitrix/templates/aspro_max/img/dark-3.png" alt="">
                        <div class="list-column-item-content">
                            <p class="small">
                                03/04
                            </p>
                            <p class="h2">
                                Фасовка
                            </p>
                            <p class="subtitle">
                                Мы всегда оперативно отгружаем заказы. Фасуем кофе сразу после обжарки. Одним из
                                помощников на производстве у нас является дозатор.
                            </p>
                        </div>
                    </li>
                    <li><img src="/bitrix/templates/aspro_max/img/dark-4.png" alt="">
                        <div class="list-column-item-content">
                            <p class="small">
                                04/04
                            </p>
                            <p class="h2">
                                Помол
                            </p>
                            <p class="subtitle">
                                Лучшая кофемолка для производства кофе мелкого помола. Оперативно сделаем «помол в
                                муку».
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>
    </div>
    <div class="height-block">
        <section class="maxwidth-theme">
            <div class="meta-items">
                <div class="h1">
                    Высокий уровень сервиса — <br>
                    не просто слова!
                </div>
                <div class="meta-item big">
                    <img src="/bitrix/templates/aspro_max/img/7.png" alt="">
                    <div class="meta-item-info">
                        <p class="h2">
                            Склад готовой продукции
                        </p>
                        <p class="body">
                            Собственный склад сырья позволяет оперативно производит чайные купажи, а со складом готовой
                            продукции мы можем оперативно отгружать сборные заказы. На складе реализовано адресное
                            хранение. Заказ будет собран оперативно и безошибочно. Перед отгрузкой мы проверяем каждый
                            заказ и формируем упаковочные листы. Мы точно знаем в какой коробке лежит ваш чай.
                        </p>
                    </div>
                </div>
                <div class="meta-item big">
                    <img src="/bitrix/templates/aspro_max/img/8.png" alt="">
                    <div class="meta-item-info">
                        <p class="h2">
                            Личный кабинет оптовика 24/7
                        </p>
                        <p class="body">
                            Вы всегда можете посмотреть актуальный ассортимент в персональном оптовом кабинете. Сейчас в
                            личном кабинете покупатель может заказать более 14 тысяч наименований товаров. Но цель
                            b2b-кабинета не просто продавать по каталогу, а предоставить клиенту полную информацию о
                            продукте.
                        </p>
                    </div>
                </div>
                <div class="meta-item big">
                    <img src="/bitrix/templates/aspro_max/img/9.png" alt="">
                    <div class="meta-item-info">
                        <p class="h2">
                            Система скидок и заказ от 1 кг
                        </p>
                        <p class="body">
                            Минимальный оптовый заказ начинается от 1 кг. одного вида чая или кофе.
                        </p>
                        <ul>
                            <li>При заказе на сумму от 15.000 рублей: Скидка на чай 3%, скидка на кофе 7%</li>
                            <li>При заказе на сумму от 50.000 рублей: Скидка на чай 6%, скидка на кофе 14%</li>
                            <li>При заказе на сумму от 100.000 рублей: Скидка на чай 10%, Скидка на кофе 20%</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="height-block">
        <section class="maxwidth-theme">
            <div class="delivery-block">
                <div class="h2">
                    Отгрузка и доставка
                </div>
                <p class="body">
                    Отгрузка товара осуществляется на следующий день после оплаты заказа. Если у вас индивидуальное
                    производство - отгрузка на 3-ий день после оплаты. Доставка осуществляется через любые транспортные
                    компани
                </p>
                <ul class="deliveries">
                    <li><img src="/bitrix/templates/aspro_max/img/lines.svg" alt=""></li>
                    <li><img src="/bitrix/templates/aspro_max/img/cdek.svg" alt=""></li>
                    <li><img src="/bitrix/templates/aspro_max/img/dpd.svg" alt=""></li>
                    <li><img src="/bitrix/templates/aspro_max/img/pek.svg" alt=""></li>
                </ul>
            </div>
        </section>
    </div>
    <div class="height-block middle">
        <section class="maxwidth-theme">
            <div class="price-list-block">
                <div>
                    <script data-b24-form="click/27/vgtvz9" data-skip-moving="true">(function (w, d, u) {
                            var s = d.createElement('script');
                            s.async = true;
                            s.src = u + '?' + (Date.now() / 180000 | 0);
                            var h = d.getElementsByTagName('script')[0];
                            h.parentNode.insertBefore(s, h);
                        })(window, document, 'https://crm.legend-tea.ru/upload/crm/form/loader_27_vgtvz9.js');</script>
                    <a class="btn  btn-transparent-border-color red  animate-load has-ripple">ПОЛУЧИТЬ ОПТОВЫЙ ПРАЙС</a>
                </div>
            </div>
        </section>
    </div>
    <!-- инициализацию слайдера решил сюда пихнуть -->
    <script>
        new Swiper('.swiper', {
            slidesPerView: 1,
            pagination: {
                el: ".swiper-pagination",
            },
        });
    </script><?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>