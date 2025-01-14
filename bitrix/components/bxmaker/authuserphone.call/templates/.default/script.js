if (!window.BXmakerAuthUserphoneCallConstructor) {

    /**
     * Обработка действий для комопнента bxmaker:authuserphone.call - аторизация, регситрация с подтверждением через смс, звонок
     * @param block
     *
     * @emits bxmaker.authuserphone.ajax {request, result, params} - событие вызывается после получения ответа на ajax запрос (смена номера отправка кода)
     *
     * @constructor
     */
    var BXmakerAuthUserphoneCallConstructor = function (block) {
        var that = this;
        that.block = block;
        that.activeBlock = 'auth';  // auth, register, codesms, usercall, usercall-check, botcall, botcoll-check, forget
        that.lastAction = 'auth';  // auth, register, forget

        // последняя операция подтверждения  - запрос или проверка, для автоповторения после ввода кода капчи
        that.lastConfirmAction = 'request'; // request, check


        that.curConfirmType = false; //текущий вараинт подтверждения
        that.data = (!!window.BXmakerAuthUserPhoneCallData && !!window.BXmakerAuthUserPhoneCallData[block.attr('data-rand')] ? window.BXmakerAuthUserPhoneCallData[block.attr('data-rand')] : false);


        that._authPhoneNumber = null;
        that._authPhoneNumberIsInit = false;
        that._authPhoneNumberIsSet = false;

        that._registerPhoneNumber = null;
        that._registerPhoneNumberIsInit = false;
        that._registerPhoneNumberIsSet = false;

        that._forgetPhoneNumber = null;
        that._forgetPhoneNumberIsInit = false;
        that._forgetPhoneNumberIsSet = false;

        that.timers = {
            'smscode': false
        };
        that.block.off();
        that.block.addClass('inited');

        //console.log('init',block.attr('data-rand'),  window.BXmakerAuthUserPhoneCallData);
    };




    BXmakerAuthUserphoneCallConstructor.prototype.getRandString = function () {
        return Math.random().toString(16).substring(2, 8) + '_' + (new Date).getTime();
    };

    BXmakerAuthUserphoneCallConstructor.prototype.init = function () {
        var that = this;

        if (that.data === false) {
            console.error('bxmaker:authuserphone.call template data is empty');
        }

        // that.block.find('input').each(function() {
        //     $(this).attr('name', that.getRandString());
        // });

        //поля заполнены или нет ---
        that.block.on("focus, blur", 'input', function () {
            var input = $(this);
            if (input.val().trim().length > 0) {
                input.closest('.bxmaker-authuserphone-call-input').addClass('bxmaker-authuserphone-call-input--filled');
            } else {
                input.closest('.bxmaker-authuserphone-call-input').removeClass('bxmaker-authuserphone-call-input--filled');
            }
        });

        //показать скрыть пароль
        that.block.on("click", '.bxmaker-authuserphone-call-input__show-pass', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var btn = $(this);

            if (btn.hasClass("bxmaker-authuserphone-call-input__show-pass--active")) {
                btn.removeClass('bxmaker-authuserphone-call-input__show-pass--active').attr('title', btn.attr('data-title-show'));
                btn.parent().find('input.bxmaker-authuserphone-call-input__field--password, input.bxmaker-authuserphone-call-input__field--register_password').prop('type', 'password');
            } else {
                btn.addClass('bxmaker-authuserphone-call-input__show-pass--active').attr('title', btn.attr('data-title-hide'));
                btn.parent().find('input.bxmaker-authuserphone-call-input__field--password, input.bxmaker-authuserphone-call-input__field--register_password').prop('type', 'text');
            }
        });

        // забыл пароль -=
        that.block.on("click", '.js-baup-forget', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.showForgot();
            that.showMessage();
        });

        that.block.on("click", '.js-baup-forget-enter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.startForgotEnter();
        });

        that.block.on("click", '.js-baup-register', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.showRegister();
            that.showMessage();
        });

        that.block.on("click", '.js-baup-register-enter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();

            if (that.data.consentShow == 'Y') {
                BX.onCustomEvent('bxmaker-authuserphone-call__consent--' + that.getRand(), []);
            } else {
                that.startRegisterEnter();
            }
        });


        that.block.on("click", '.js-baup-auth', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.showAuth();
            that.showMessage();
        });

        that.block.on("click", '.js-baup-auth-enter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.startAuthEnter();
        });

        that.block.on("click", '.js-baup-captcha-reload', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.reloadCaptcha();
        });

        // получить код в смс
        that.block.on("click", '.js-baup-sendcode', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.startSendSmsCode();
        });
        // подтверждение смскода
        that.block.on("click", '.js-baup-smscode-next', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.checkSmsCode();
        });
        // ввод смскода
        that.block.find("input[name='smscode']").on("keyup", function (e) {
            var input = $(this);
            if (input.attr('data-length') && +input.attr('data-length') > 0 && input.val().trim().length == +input.attr('data-length')) {
                that.hideErrorOrMessage();
                that.checkSmsCode();
            }
        });

        // завпрос номера бота на который должен позвонить пользователь
        that.block.on("click", '.js-baup-get-callphone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.startUserCall();
        });

        // проверка совершил ли пользователь звонок
        that.block.on("click", '.js-baup-usercall-next', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.checkUserCall();
        });

        // запрос звонк аот робота
        that.block.on("click", '.js-baup-get-botcall', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.startBotCall();
        });

        // проверка код из номера от бота
        that.block.on("click", '.js-baup-botcall-next', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.checkBotCall();
        });

        // ввод кода из номера телефона
        that.block.find("input[name='botcode']").on("keyup", function (e) {
            var input = $(this);
            if (input.attr('data-length') && +input.attr('data-length') > 0 && input.val().trim().length == +input.attr('data-length')) {
                that.hideErrorOrMessage();
                that.checkBotCall();
            }
        });

        // запрос голосового кода
        that.block.on("click", '.js-baup-get-botspeech', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.startBotSpeech();
        });


        // проверка голосового кода
        that.block.on("click", '.js-baup-botspeech-next', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.checkBotSpeech();
        });


        // ввод голосового кода
        that.block.find("input[name='botspeech']").on("keyup", function (e) {
            var input = $(this);
            var length = +input.attr('data-length');
            if (length > 0 && input.val().trim().length == length) {
                that.hideErrorOrMessage();
                that.checkBotSpeech();
            }
        });


        //смена варианта подтверждения действия
        that.block.on("click", '.js-baup-change-confirm', function (e) {
            e.preventDefault();
            e.stopPropagation();

            that.hideErrorOrMessage();
            that.showMessage(null);
            that.changeCurConfirmType();
            that.hideCaptcha();
            that.setLastConfirmActionIsRequest();
            that.showConfirmBlock();
        });

        //кнопка назад
        that.block.on("click", '.js-baup-back', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            that.showBlock(that.getLastAction());
        });


        // по нажатию на интер - автоклик по кнопке войти
        that.block.on("keyup", 'input.js-on-enter-continue', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (e.keyCode == 13) {
                // нажимаем на кнопку продолжить в активном блоке
                $(this).closest('.bxmaker-authuserphone-call__block').find('.js-baup-continue').click();

            }
        });

        // выход
        that.block.on("click", '.js-btn-logout', function (e) {
            e.preventDefault();
            e.stopPropagation();
            that.hideErrorOrMessage();
            location.href = location.pathname + (location.search.length > 0 ? location.search + '&' : '?') + 'logout=Y';
        });

        that.block.on("keyup", 'input.bxmaker-authuserphone-call-input__field--captchaCode', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (e.keyCode === 13 || +e.target.value.length === +e.target.dataset.length) {
                that.onCaptchaComplete();
            }
        });


        //показ регистрации если в хэше есть отметка
        if (location.hash == "#reg" && that.data.isEnabledRegister) {
            that.showRegister();
        }


        if (that.data.consentShow == 'Y' && !!BX.UserConsent) {

            var control = BX.UserConsent.load(BX('bxmaker-authuserphone-call--' + that.getRand()));

            BX.addCustomEvent(
                control,
                BX.UserConsent.events.save,
                function (data) {
                    that.startRegisterEnter({
                        'consent': 1,
                        'consent_id': data.id,
                        'consent_sec': data.sec,
                        'consent_url': data.url
                    });
                }
            );
        }

        //если всего 1 вариант подтверждения, то не показываем кнпоку смены варианта подтверждения
        if (that.getConfirmQueue().length <= 1) {
            that.block.addClass('bxmaker-authuserphone-call--easyconfirm');
        }

        // проверяем заполненость полей
        that.block.find('.bxmaker-authuserphone-call-input input').each(function () {
            var input = $(this);
            if (input.val().trim().length > 0) {
                input.closest('.bxmaker-authuserphone-call-input').addClass('bxmaker-authuserphone-call-input--filled');
            } else {
                input.closest('.bxmaker-authuserphone-call-input').removeClass('bxmaker-authuserphone-call-input--filled');
            }
        });

        // маска
        if (that.data.phoneMaskParams && that.data.phoneMaskParams.type && that.data.phoneMaskParams.type === 'bitrix') {

            var authPhoneInput = that.block.find('input.bxmaker-authuserphone-call-input__field--phone');
            if (authPhoneInput.length) {
                that._authPhoneNumber = new BX.PhoneNumber.Input({
                    node: authPhoneInput.get(0),
                    flagNode: that.block.find('.bxmaker-authuserphone-call__block--auth .bxmaker-authuserphone-call-input__flag').get(0),
                    flagSize: 16, // Размер флага [16, 24, 32]
                    defaultCountry: that.data.phoneMaskParams.defaultCountry, // Страна по-умолчанию
                    countryTopList: that.data.phoneMaskParams.countryTopList,
                    onInitialize: function (e) {
                        setTimeout(function () {
                            that._authPhoneNumber.formatter.replaceCountry(that.data.phoneMaskParams.defaultCountry);
                            that._authPhoneNumberIsInit = true;
                        }, 50);
                    },
                    onChange: function (data) {
                        authPhoneInput.focus();
                    }
                });
                authPhoneInput.on("focus", function (e) {
                    var input = $(this);
                    if (that._authPhoneNumberIsInit && !that._authPhoneNumberIsSet) {
                        that._authPhoneNumberIsSet = true;
                        if (!input.val().length) {
                            input.val(that._authPhoneNumber.formatter.getFormattedNumber());
                        }
                    }
                });

                that.block.find('.bxmaker-authuserphone-call__block--auth .bxmaker-authuserphone-call-input__flag').on("click", function(){
                   that.checkPhoneCountryPopupPosition();
                });
            }


            //-------------------------------------
            var regPhoneInput = that.block.find('input.bxmaker-authuserphone-call-input__field--register_phone');
            if (regPhoneInput.length) {
                that._registerPhoneNumber = new BX.PhoneNumber.Input({
                    node: regPhoneInput.get(0),
                    flagNode: that.block.find('.bxmaker-authuserphone-call__block--register .bxmaker-authuserphone-call-input__flag').get(0),
                    flagSize: 16, // Размер флага [16, 24, 32]
                    defaultCountry: that.data.phoneMaskParams.defaultCountry, // Страна по-умолчанию
                    countryTopList: that.data.phoneMaskParams.countryTopList,
                    onInitialize: function (e) {
                        setTimeout(function () {
                            that._registerPhoneNumber.formatter.replaceCountry(that.data.phoneMaskParams.defaultCountry);
                            that._registerPhoneNumberIsInit = true;
                        }, 50);
                    },
                    onChange: function (data) {
                        regPhoneInput.focus();
                    }
                });
                regPhoneInput.on("focus", function (e) {
                    var input = $(this);
                    if (that._registerPhoneNumberIsInit && !that._registerPhoneNumberIsSet) {
                        that._registerPhoneNumberIsSet = true;
                        if (!input.val().length) {
                            input.val(that._registerPhoneNumber.formatter.getFormattedNumber());
                        }

                    }
                });

                that.block.find('.bxmaker-authuserphone-call__block--register .bxmaker-authuserphone-call-input__flag').on("click", function(){
                    that.checkPhoneCountryPopupPosition();
                });
            }

            //----------------------------------
            var forgetPhoneInput = that.block.find('input.bxmaker-authuserphone-call-input__field--forget_phone');
            if (forgetPhoneInput.length) {
                that._forgetPhoneNumber = new BX.PhoneNumber.Input({
                    node: forgetPhoneInput.get(0),
                    flagNode: that.block.find('.bxmaker-authuserphone-call__block--forget .bxmaker-authuserphone-call-input__flag').get(0),
                    flagSize: 16, // Размер флага [16, 24, 32]
                    defaultCountry: that.data.phoneMaskParams.defaultCountry, // Страна по-умолчанию
                    countryTopList: that.data.phoneMaskParams.countryTopList,
                    onInitialize: function (e) {
                        setTimeout(function () {
                            that._forgetPhoneNumber.formatter.replaceCountry(that.data.phoneMaskParams.defaultCountry);
                            that._forgetPhoneNumberIsInit = true;
                        }, 50);
                    },
                    onChange: function (data) {
                        forgetPhoneInput.focus();
                    }
                });
                forgetPhoneInput.on("focus", function (e) {
                    var input = $(this);
                    if (that._forgetPhoneNumberIsInit && !that._forgetPhoneNumberIsSet) {
                        that._forgetPhoneNumberIsSet = true;
                        if (!input.val().length) {
                            input.val(that._forgetPhoneNumber.formatter.getFormattedNumber());
                        }
                    }
                });

                that.block.find('.bxmaker-authuserphone-call__block--forget .bxmaker-authuserphone-call-input__flag').on("click", function(){
                    that.checkPhoneCountryPopupPosition();
                });
            }


        }

        BX.addCustomEvent('onAjaxSuccess', BX.proxy(this.onAjaxSuccess, this));
    };




    BXmakerAuthUserphoneCallConstructor.prototype.checkPhoneCountryPopupPosition = function () {
        setTimeout(() => {
            let phoneCountryPopup = BX('phoneNumberInputSelectCountry');
            if (phoneCountryPopup) {
                BX.style(phoneCountryPopup, 'z-index', '4000');
            }
            let phoneCountryPopupBg = BX('popup-window-overlay-phoneNumberInputSelectCountry');
            if (phoneCountryPopupBg) {
                BX.style(phoneCountryPopupBg, 'z-index', '3995');
            }
        }, 5);
    };

    BXmakerAuthUserphoneCallConstructor.prototype.onAjaxSuccess = function (data, request) {
        if (!request || !request.url || !request.url.match(/action=main\.phonenumber\.getCountries$/)) {
            return true;
        }
        this.checkPhoneCountryPopupPosition();
    };

    /**
     * Возвращает сообщение
     * @param name
     * @returns {string}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.getMessage = function (name) {
        return (('messages' in this.data && name in this.data.messages) ? this.data.messages[name] : '');
    };

    BXmakerAuthUserphoneCallConstructor.prototype.getConfirmTypeSmsCode = function (name) {
        return 1;
    };
    BXmakerAuthUserphoneCallConstructor.prototype.getConfirmTypeUserCall = function (name) {
        return 2;
    };
    BXmakerAuthUserphoneCallConstructor.prototype.getConfirmTypeBotCall = function (name) {
        return 3;
    };

    BXmakerAuthUserphoneCallConstructor.prototype.getConfirmTypeBotSpeech = function (name) {
        return 4;
    };

    /**
     * Объединение объектов
     * @param a
     * @param b
     */
    BXmakerAuthUserphoneCallConstructor.prototype.assign = function (a, b) {
        var that = this;
        var c = a, key;
        for (key in b) {
            if (typeof (b[key]) == 'object') {
                if (a.hasOwnProperty(key) && typeof (a[key]) == 'object') {
                    c[key] = that.assign(a[key], b[key]);
                } else {
                    c[key] = b[key];
                }
            } else {
                c[key] = b[key];
            }
        }
        return c;
    };

    /**
     * Возвращает порядок  доступных способов подтверждения
     * @returns {string[]}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.getConfirmQueue = function () {
        var that = this;
        var confirmQueue = (!!this.data && 'confirmQueue' in this.data ? this.data['confirmQueue'] : [
            that.getConfirmTypeSmsCode(), that.getConfirmTypeUserCall(), that.getConfirmTypeBotCall(), that.getConfirmTypeBotSpeech()
        ]);

        if (!that.data.isEnabledConfirmBySmsCode) {
            confirmQueue = confirmQueue.filter(row => row !== that.getConfirmTypeSmsCode());
        }
        if (!that.data.isEnabledConfirmByUserCall) {
            confirmQueue = confirmQueue.filter(row => row !== that.getConfirmTypeUserCall());
        }
        if (!that.data.isEnabledConfirmByBotCall) {
            confirmQueue = confirmQueue.filter(row => row !== that.getConfirmTypeBotCall());
        }
        if (!that.data.isEnabledConfirmByBotSpeech) {
            confirmQueue = confirmQueue.filter(row => row !== that.getConfirmTypeBotSpeech());
        }

        if (!confirmQueue.length) {
            confirmQueue.push(that.getConfirmTypeSmsCode());
        }

        return confirmQueue;
    };


    BXmakerAuthUserphoneCallConstructor.prototype.hideBlocks = function () {
        var that = this;
        that.block.find('.bxmaker-authuserphone-call__block').hide(300);
        that.block.find('.bxmaker-authuserphone-call-title').text(that.getMessage('authTitle'));
    };

    /**
     * Показ блока по названию, скрытие остальных блоков
     * @param name
     */
    BXmakerAuthUserphoneCallConstructor.prototype.showBlock = function (name) {
        var that = this;
        that.block.find('.bxmaker-authuserphone-call__block:not(.bxmaker-authuserphone-call__block--' + name + ')').hide(300);
        that.block.find('.bxmaker-authuserphone-call__block--' + name + '').show(300);

        that.setActiveBlockName(name);

        if (that.getMessage(name + 'Title')) {
            that.block.find('.bxmaker-authuserphone-call-title').text(that.getMessage(name + 'Title'));
        }

        $(document).trigger('bxmaker.authuserphone.call.changeBlock', {'show': name});
    };

    /**
     * Возвращает название активного блока
     * @returns {string}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.getActiveBlockName = function () {
        var that = this;
        return that.activeBlock;
    };

    /**
     * Возвращает название активного блока
     * @returns {string}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.setActiveBlockName = function (name) {
        var that = this;
        that.activeBlock = name;
    };


    /**
     * Вернет ссылку на блок по имени
     * @param name
     * @returns {string}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.getBlockByName = function (name) {
        var that = this;
        return that.block.find('.bxmaker-authuserphone-call__block--' + name + '');
    };

    /**
     * Вернет ссылку на DOM element активного блока
     * @returns {jquery}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.getActiveBlock = function () {
        return this.getBlockByName(this.getActiveBlockName());
    };


    /**
     * Указывает последнее действие - регшистрация, авторизация, восстанволение доступа,
     * для понимания какой действие подтверждается смс, завонком и тп
     * @param action
     */
    BXmakerAuthUserphoneCallConstructor.prototype.setLastAction = function (action) {
        var that = this;
        that.lastAction = action;
    };

    /**
     * Возвращает название последнего действия, для которого происходит подтверждение
     * @returns {string|*}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.getLastAction = function () {
        var that = this;
        return that.lastAction;
    };

    /**
     * Рандомная строка данного блока
     * @returns {*}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.getRand = function () {
        var that = this;
        return that.block.attr('data-rand');
    };


    /**
     * показ блока регистрации
     */
    BXmakerAuthUserphoneCallConstructor.prototype.showRegister = function () {
        var that = this;
        that.showBlock('register');
    };

    /**
     * показ блока авторизации
     */
    BXmakerAuthUserphoneCallConstructor.prototype.showAuth = function () {
        var that = this;
        that.showBlock('auth');
    };

    BXmakerAuthUserphoneCallConstructor.prototype.showForgot = function () {
        var that = this;
        that.showBlock('forget');
    };


    /**
     * Вывод сообщения
     * @param text
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.showMessage = function (text) {
        var that = this;
        var msgBox = that.block.find('.bxmaker-authuserphone-call-msg');

        msgBox.removeClass('bxmaker-authuserphone-call-msg--success bxmaker-authuserphone-call-msg--error').empty();

        if (!text) {
            return true;
        }

        msgBox.addClass('bxmaker-authuserphone-call-msg--success').html(text);
    };

    /**
     * Показ ошибки
     * @param text
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.showError = function (text) {
        var that = this;
        var msgBox = that.block.find('.bxmaker-authuserphone-call-msg');

        msgBox.removeClass('bxmaker-authuserphone-call-msg--success bxmaker-authuserphone-call-msg--error').empty();

        if (!text) {
            return true;
        }

        msgBox.addClass('bxmaker-authuserphone-call-msg--error').html(text);
    };

    /**
     * Скрывает ошибку или сообщений
     * @param text
     */
    BXmakerAuthUserphoneCallConstructor.prototype.hideErrorOrMessage = function (text) {
        var that = this;
        var msgBox = that.block.find('.bxmaker-authuserphone-call-msg');

        msgBox.removeClass('bxmaker-authuserphone-call-msg--success bxmaker-authuserphone-call-msg--error').empty();
    };


    /**
     * Действия на ответ после ajax  запроса
     * @param r
     */
    BXmakerAuthUserphoneCallConstructor.prototype.checkNeedRedirect = function (r) {
        var that = this;
        if (!!r && !!r.response && !!r.response.redirect) {
            location.href = r.response.redirect;
            return true;
        }
        return false;
    };

    /**
     * Проверка необходимости перезагрузки страницы после успешной авторизации
     * @param r
     */
    BXmakerAuthUserphoneCallConstructor.prototype.checkNeedReload = function (r) {
        var that = this;
        if (!!r && !!r.response && !!r.response.reload) {
            location.reload();
            return true;
        }
        return false;
    };

    /**
     * Проверка необходимости отображить капчу
     * @param r
     */
    BXmakerAuthUserphoneCallConstructor.prototype.checkNeedShowCaptcha = function (r) {
        var that = this;
        if (!!r && !!r.error && !!r.error.more && !!r.error.more.captchaId) {
            that.showCaptcha(r.error.more);
        }
    };

    /**
     * Показ капчи по  коду и пути до картинки
     * @param id
     * @param src
     */
    BXmakerAuthUserphoneCallConstructor.prototype.showCaptcha = function (result) {
        var that = this;

        that.getActiveBlock().find('.bxmaker-authuserphone-call-captcha').show()
            .html('<input type="hidden" name="captchaId" value="' + result.captchaId + '" class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--captchaId"/>' +
                '<img src="' + result.captchaSrc + '" title="' + that.getMessage('updateCaptcha') + '" class="bxmaker-authuserphone-call-captcha__img js-baup-captcha-reload"/>' +
                '<span class="bxmaker-authuserphone-call-captcha__btn-reload js-baup-captcha-reload" title="' + that.getMessage('updateCaptcha') + '"></span>' +
                '<div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top">' +
                '<input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--captchaCode" type="text" name="captchaCode" id="bxmaker-authuserphone-call__input-captcha_code' + that.getRand()
                + '" maxlength="' + result.captchaLength + '" data-length="' + result.captchaLength + '" autocomplete="off">' +
                '<label class="bxmaker-authuserphone-call-input__label" for="bxmaker-authuserphone-call__input-captcha_code' + that.getRand() + '">' +
                '<span class="bxmaker-authuserphone-call-input__label-text">' + that.getMessage('inputCaptchaWord') + '</span>' +
                '</label>' +
                '</div>').find('input.bxmaker-authuserphone-call-input__field--captchaCode').focus();


    };

    /**
     * Скрытие капчи
     */
    BXmakerAuthUserphoneCallConstructor.prototype.hideCaptcha = function () {
        var that = this;
        that.block.find('.bxmaker-authuserphone-call-captcha').empty().hide();
    };

    /**
     * Обновление капчи
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.reloadCaptcha = function () {
        var that = this;
        var box = that.getActiveBlock();
        var btn = box.find('.bxmaker-authuserphone-call-captcha__btn-reload');

        if (btn.hasClass('preloader')) return false;
        btn.addClass('preloader');

        var data = {
            sessid: BX.bitrix_sessid(),
            parameters: that.data.parameters,
            template: that.data.template,
            siteId: that.data.siteId,
            method: 'refreshCaptcha'
        };

        $.ajax({
            url: that.data.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (r) {
                btn.removeClass('preloader');
            },
            success: function (r) {

                // событие получения ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': that.data,
                    'request': data,
                    'result': r,
                });

                btn.removeClass('preloader');

                if (!!r.error) {
                    that.showError(r.error.msg);
                } else if (!!r.response) {
                    that.showCaptcha(r.response);
                }

            }
        });

    };


    /**
     * Проверка необходимости подтвердить действие по смс, звонком и тп
     * @param r
     */
    BXmakerAuthUserphoneCallConstructor.prototype.checkNeedConfirm = function (r) {
        var that = this;
        if (!!r && !!r.error && !!r.error.code && r.error.code == 'ERROR_NEED_CONFIRM') {
            that.showConfirmBlock(r.error.more);
        }
    };

    /**
     * Возвращает тип текущего варианта подтверждения действия
     * @param r
     */
    BXmakerAuthUserphoneCallConstructor.prototype.getCurConfirmType = function (r) {
        var that = this;
        if (!this.curConfirmType) {
            var types = that.getConfirmQueue();
            if (types.length <= 0) {
                this.curConfirmType = that.getConfirmTypeSmsCode();
            } else {

                this.curConfirmType = types[0];
            }
        }
        return this.curConfirmType;
    };

    /**
     * Меняет текущий вариант подтверждения на следующий из списка достпных
     */
    BXmakerAuthUserphoneCallConstructor.prototype.changeCurConfirmType = function () {
        var that = this;
        var ar = that.getConfirmQueue();
        var index = ar.indexOf(this.curConfirmType);

        if (index === -1) {
            index = 0;
        } else if (index + 1 >= ar.length) {
            index = 0;
        } else {
            index++;
        }

        this.curConfirmType = ar[index];
    };


    /**
     * Показывает вариант подтверждения
     * @param data
     */
    BXmakerAuthUserphoneCallConstructor.prototype.showConfirmBlock = function (data) {
        var that = this;
        switch (that.getCurConfirmType()) {
            case that.getConfirmTypeUserCall(): {
                that.showConfirmByUserCall(data);
                break;
            }
            case that.getConfirmTypeBotCall(): {
                that.showConfirmByBotCall(data);
                break;
            }
            case that.getConfirmTypeBotSpeech(): {
                that.showConfirmByBotSpeech(data);
                break;
            }
            default: {
                that.showConfirmBySmsCode(data);
            }
        }
    };


    /**
     * Вход по номеру телефона и паролю
     */
    BXmakerAuthUserphoneCallConstructor.prototype.startAuthEnter = function (params) {
        var that = this;
        var btn = that.block.find('.js-baup-auth-enter');
        var authBox = that.getBlockByName('auth');
        var params = params || {};

        if (btn.hasClass('preloader')) return false;
        btn.addClass('preloader');

        that.setLastAction('auth');

        var data = {
            sessid: BX.bitrix_sessid(),
            parameters: that.data.parameters,
            template: that.data.template,
            siteId: that.data.siteId,
            method: 'auth',
            phone: authBox.find('input.bxmaker-authuserphone-call-input__field--phone').val(),
            password: authBox.find('input.bxmaker-authuserphone-call-input__field--password').val()
        };

        if (authBox.find('input.bxmaker-authuserphone-call-input__field--captchaCode').length) {
            data.captchaId = authBox.find('input.bxmaker-authuserphone-call-input__field--captchaId').val();
            data.captchaCode = authBox.find('input.bxmaker-authuserphone-call-input__field--captchaCode').val();
        }

        //замена парамтеров
        data = that.assign(data, params);
        delete data.callback;


        $.ajax({
            url: that.data.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (r) {
                that.hideCaptcha();
                btn.removeClass('preloader');

                if ('callback' in params && typeof (params.callback) == 'function') {
                    params.callback(r);
                }
            },
            success: function (r) {
                that.hideCaptcha();

                // событие получения ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': that.data,
                    'request': data,
                    'result': r,
                });

                btn.removeClass('preloader');

                //повтор при смене сессии --
                if (!!r.error && r.error.code == 'INVALID_SESSID' && r.error.more && r.error.more.sessid && !params.repeatRequest) {
                    BX.message({"bitrix_sessid": r.error.more.sessid});
                    params.repeatRequest = 1;
                    that.startAuthEnter(params);
                    return false;
                }

                if(!that.checkNeedRedirect(r))
                {
                    that.checkNeedReload(r);
                }


                that.checkNeedConfirm(r);
                that.checkNeedShowCaptcha(r);

                if (!!r.error) {
                    switch (r.error.code) {
                        case 'ERROR_NEED_CONFIRM': {
                            break;
                        }
                        default: {
                            that.showError(r.error.msg);
                            break;
                        }
                    }
                } else if (!!r.response) {
                    that.showMessage(r.response.msg);
                }

                if ('callback' in params && typeof (params.callback) == 'function') {
                    params.callback(r);
                }
            }
        });


    };

    /**
     * Регистрация
     * @param params
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.startRegisterEnter = function (params) {
        var that = this;
        var btn = that.block.find('.js-baup-register-enter');
        var box = that.getBlockByName('register');
        var params = params || {};

        if (btn.hasClass('preloader')) return false;
        btn.addClass('preloader');

        that.setLastAction('register');

        var data = {
            sessid: BX.bitrix_sessid(),
            parameters: that.data.parameters,
            template: that.data.template,
            siteId: that.data.siteId,
            method: 'register',
            phone: box.find('input.bxmaker-authuserphone-call-input__field--register_phone').val(),
            password: box.find('input.bxmaker-authuserphone-call-input__field--register_password').val(),
            login: box.find('input.bxmaker-authuserphone-call-input__field--register_login').val(),
            email: box.find('input.bxmaker-authuserphone-call-input__field--register_email').val(),
        };

        //капча
        if (box.find('input.bxmaker-authuserphone-call-input__field--captchaCode').length) {
            data.captchaId = box.find('input.bxmaker-authuserphone-call-input__field--captchaId').val();
            data.captchaCode = box.find('input.bxmaker-authuserphone-call-input__field--captchaCode').val();
        }

        //согласие
        if (!!params && !!params.consent) {
            data.consent = data.consent;
            data.consent_id = data.id;
            data.consent_sec = data.sec;
            data.consent_url = data.url;
        }

        //замена парамтеров
        data = that.assign(data, params);
        delete data.callback;


        $.ajax({
            url: that.data.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (r) {
                that.hideCaptcha();
                btn.removeClass('preloader');

                if ('callback' in params && typeof (params.callback) == 'function') {
                    params.callback(r);
                }
            },
            success: function (r) {
                that.hideCaptcha();

                // событие получения ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': that.data,
                    'request': data,
                    'result': r,
                });


                btn.removeClass('preloader');

                //повтор при смене сессии --
                if (!!r.error && r.error.code == 'INVALID_SESSID' && r.error.more && r.error.more.sessid && !params.repeatRequest) {
                    BX.message({"bitrix_sessid": r.error.more.sessid});
                    params.repeatRequest = 1;
                    that.startRegisterEnter(params);
                    return false;
                }


                if(!that.checkNeedRedirect(r))
                {
                    that.checkNeedReload(r);
                }

                that.checkNeedConfirm(r);
                that.checkNeedShowCaptcha(r);

                if (!!r.error) {
                    switch (r.error.code) {
                        case 'ERROR_NEED_CONFIRM': {
                            break;
                        }
                        default: {
                            that.showError(r.error.msg);
                            break;
                        }
                    }
                } else if (!!r.response) {
                    that.showMessage(r.response.msg);
                }

                if ('callback' in params && typeof (params.callback) == 'function') {
                    params.callback(r);
                }

            }
        });

    };

    /**
     * Восстановление доступа по email или номеру телефона
     * @param params
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.startForgotEnter = function (params) {
        var that = this;
        var btn = that.block.find('.js-baup-forget-enter');
        var box = that.getBlockByName('forget');
        var params = params || {};

        if (btn.hasClass('preloader')) return false;
        btn.addClass('preloader');

        that.setLastAction('forget');

        var data = {
            sessid: BX.bitrix_sessid(),
            parameters: that.data.parameters,
            template: that.data.template,
            siteId: that.data.siteId,
            method: 'forget',
            phone: box.find('input.bxmaker-authuserphone-call-input__field--forget_phone').val(),
            email: box.find('input.bxmaker-authuserphone-call-input__field--forget_email').val()
        };

        //капча
        if (box.find('input.bxmaker-authuserphone-call-input__field--captchaCode').length) {
            data.captchaId = box.find('input.bxmaker-authuserphone-call-input__field--captchaId').val();
            data.captchaCode = box.find('input.bxmaker-authuserphone-call-input__field--captchaCode').val();
        }

        //замена парамтеров
        data = that.assign(data, params);
        delete data.callback;


        $.ajax({
            url: that.data.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (r) {
                that.hideCaptcha();
                btn.removeClass('preloader');

                if ('callback' in params && typeof (params.callback) == 'function') {
                    params.callback(r);
                }
            },
            success: function (r) {
                that.hideCaptcha();

                // событие получения ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': that.data,
                    'request': data,
                    'result': r,
                });


                btn.removeClass('preloader');

                //повтор при смене сессии --
                if (!!r.error && r.error.code == 'INVALID_SESSID' && r.error.more && r.error.more.sessid && !params.repeatRequest) {
                    BX.message({"bitrix_sessid": r.error.more.sessid});
                    params.repeatRequest = 1;
                    that.startForgotEnter(params);
                    return false;
                }

                if(!that.checkNeedRedirect(r))
                {
                    that.checkNeedReload(r);
                }

                that.checkNeedConfirm(r);
                that.checkNeedShowCaptcha(r);

                if (!!r.error) {
                    switch (r.error.code) {
                        case 'ERROR_NEED_CONFIRM': {

                            break;
                        }
                        default: {
                            that.showError(r.error.msg);
                            break;
                        }
                    }
                } else if (!!r.response) {
                    that.showMessage(r.response.msg);
                    that.onAuth();
                }

                if ('callback' in params && typeof params.callback == 'function') {
                    params.callback(r);
                }
            }
        });

    };

    /**
     * Показывает блок подтверждение действия по коду из смс
     * @param data
     */
    BXmakerAuthUserphoneCallConstructor.prototype.showConfirmBySmsCode = function () {
        var that = this;
        that.showBlock('smscode');

        //кликаем по кнопке отправить смс за пользователя
        that.startSendSmsCode();

    };

    /**
     * Показывает блок подтверждения действия по звонку пользвоателя
     */
    BXmakerAuthUserphoneCallConstructor.prototype.showConfirmByUserCall = function () {
        var that = this;
        that.showBlock('usercall');
        that.startUserCall();
    };

    /**
     * Показывает блок подтверждения действия по звонку робота пользователю
     */
    BXmakerAuthUserphoneCallConstructor.prototype.showConfirmByBotCall = function () {
        var that = this;

        that.hideErrorOrMessage();
        that.startBotCall();
        that.showBlock('botcall');

    };

    /**
     * Показывает блок подтверждения действия через голосовой код
     */
    BXmakerAuthUserphoneCallConstructor.prototype.showConfirmByBotSpeech = function () {
        var that = this;

        that.hideErrorOrMessage();
        that.startBotSpeech();
        that.showBlock('botspeech');

    };

    // показ обратного отсчета до следующей отправки смс кода
    BXmakerAuthUserphoneCallConstructor.prototype.showSmsCodeTimeout = function (timeout) {
        var that = this;
        var btn = that.block.find('.js-baup-sendcode');

        timeout = (!!timeout ? timeout : 59);

        if (that.timers.smscode) {
            clearInterval(that.timers.smscode);
        }

        // индикатор
        that.timers.smscode = setInterval(function () {
            if (--timeout > 0) {
                btn.text(that.getMessage('sms_code_timeout').replace(/#TIMEOUT#/, timeout));
            } else {
                clearInterval(that.timers.smscode);
                btn.text(that.getMessage('sms_code_request'));
                btn.removeClass("timeout");
            }
        }, 1000);

        //сразу отображаем
        btn.text(that.getMessage('sms_code_timeout').replace(/#TIMEOUT#/, timeout)).addClass('timeout');

    };

    /**
     * Запрос на отправку кода в смс
     * @param params
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.startSendSmsCode = function (params) {
        var that = this;
        var btn = that.block.find('.js-baup-sendcode');
        var boxLast = that.getBlockByName(that.getLastAction());
        var boxCurrent = that.getBlockByName('smscode');


        if (btn.hasClass('preloader') || btn.hasClass('timeout')) return false;
        btn.addClass('preloader');

        var data = {
            sessid: BX.bitrix_sessid(),
            parameters: that.data.parameters,
            template: that.data.template,
            siteId: that.data.siteId,
            method: 'sendCode',
            phone: boxLast.find('input.bxmaker-authuserphone-call-input__field--phone, input.bxmaker-authuserphone-call-input__field--register_phone, input.bxmaker-authuserphone-call-input__field--forget_phone').val()
        };

        //капча
        if (boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').length) {
            data.captchaId = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaId').val();
            data.captchaCode = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').val();
        }

        that.setLastConfirmActionIsRequest();

        $.ajax({
            url: that.data.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (r) {
                that.hideCaptcha();
                btn.removeClass('preloader');
            },
            success: function (r) {
                that.hideCaptcha();

                // событие получения ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': that.data,
                    'request': data,
                    'result': r,
                });

                var timeout = 0;

                btn.removeClass("preloader");

                //фокус на поле
                boxCurrent.find('input.bxmaker-authuserphone-call-input__field--smscode').val('').focus();

                that.checkNeedShowCaptcha(r);

                if (!!r.response) {
                    that.showMessage(r.response.msg);

                    if (r.response.length) {
                        boxCurrent.find('input.bxmaker-authuserphone-call-input__field--smscode').attr('data-length', r.response.length);
                    }

                    if (!!r.response.timeout) {
                        that.showSmsCodeTimeout(r.response.timeout);

                    } else {
                        if (that.timers.smscode) {
                            clearInterval(that.timers.smscode);
                        }
                        btn.text(that.getMessage('sms_code_request')).removeClass("timeout");
                    }
                } else if (!!r.error) {

                    that.showError(r.error.msg);

                    if (!!r.error.more && !!r.error.more.timeout) {
                        that.showSmsCodeTimeout(r.error.more.timeout);
                    } else {
                        if (that.timers.smscode) {
                            clearInterval(that.timers.smscode);
                        }
                        btn.text(that.getMessage('sms_code_request')).removeClass("timeout");
                    }
                }

            }
        });

    };

    /**
     * Проверка смс кода
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.checkSmsCode = function () {
        var that = this;
        var btn = that.block.find('.js-baup-smscode-next');
        var boxLast = that.getBlockByName(that.getLastAction());
        var boxCurrent = that.getBlockByName('smscode');
        var data = {
            confirmType: that.getConfirmTypeSmsCode()
        };

        if (btn.hasClass('preloader')) return false;
        btn.addClass('preloader');

        //капча
        if (boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').length) {
            data.captchaId = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaId').val();
            data.captchaCode = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').val();
        }

        data.confirmValue = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--smscode').val();

        data.callback = function (r) {
            btn.removeClass('preloader');
        };
        that.confirmLastAction(data);
    };

    // показ обратного отсчета до следующего запроса номера телеофна
    BXmakerAuthUserphoneCallConstructor.prototype.showUserCallTimeout = function (timeout) {
        var that = this;
        var btn = that.block.find('.js-baup-get-callphone');

        timeout = (!!timeout ? timeout : 59);

        if (that.timers.usercall) {
            clearInterval(that.timers.usercall);
        }

        // индикатор
        that.timers.usercall = setInterval(function () {
            if (--timeout > 0) {
                btn.text(that.getMessage('user_call_timeout').replace(/#TIMEOUT#/, timeout));
            } else {
                clearInterval(that.timers.usercall);
                btn.text(that.getMessage('user_call_request'));
                btn.removeClass("timeout");
            }
        }, 1000);

        //сразу отображаем
        btn.text(that.getMessage('user_call_timeout').replace(/#TIMEOUT#/, timeout)).addClass('timeout');

    };

    /**
     * Запрос номера телефона для показа, на который должен позвонить пользователя для подтверждения действия
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.startUserCall = function () {
        var that = this;
        var btn = that.block.find('.js-baup-get-callphone');
        var boxLast = that.getBlockByName(that.getLastAction());
        var boxCurrent = that.getBlockByName('usercall');

        if (btn.hasClass('preloader') || btn.hasClass('timeout')) return false;
        btn.addClass('preloader');

        var data = {
            sessid: BX.bitrix_sessid(),
            parameters: that.data.parameters,
            template: that.data.template,
            siteId: that.data.siteId,
            method: 'userCall',
            phone: boxLast.find('input.bxmaker-authuserphone-call-input__field--phone, input.bxmaker-authuserphone-call-input__field--register_phone, input.bxmaker-authuserphone-call-input__field--forget_phone').val()
        };

        //капча
        if (boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').length) {
            data.captchaId = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaId').val();
            data.captchaCode = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').val();
        }

        that.setLastConfirmActionIsRequest();

        $.ajax({
            url: that.data.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (r) {
                that.hideCaptcha();
                btn.removeClass('preloader');
            },
            success: function (r) {
                that.hideCaptcha();

                // событие получения ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': that.data,
                    'request': data,
                    'result': r,
                });


                var timeout = 0;
                that.checkNeedShowCaptcha(r);
                btn.removeClass("preloader");


                if (!!r.response) {
                    boxCurrent.find('input.bxmaker-authuserphone-call-input__field--callphone').val(r.response.callTo);

                    if (!!r.response.timeout) {
                        that.showUserCallTimeout(r.response.timeout);

                    } else {
                        if (that.timers.usercall) {
                            clearInterval(that.timers.usercall);
                        }
                        btn.text(that.getMessage('user_call_request')).removeClass("timeout");
                    }

                } else if (!!r.error) {
                    that.showError(r.error.msg);

                    if (!!r.error.more && !!r.error.more.timeout) {
                        that.showUserCallTimeout(r.error.more.timeout);
                    } else {
                        if (that.timers.usercall) {
                            clearInterval(that.timers.usercall);
                        }
                        btn.text(that.getMessage('user_call_request')).removeClass("timeout");
                    }
                }

            }
        });

    };


    /**
     * Проверка звонка пользователя
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.checkUserCall = function () {
        var that = this;
        var btn = that.block.find('.js-baup-usercall-next');
        var boxCurrent = that.getBlockByName('usercall');
        var data = {
            confirmType: that.getConfirmTypeUserCall()
        };

        if (btn.hasClass('preloader')) return false;
        btn.addClass('preloader');

        //капча
        if (boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').length) {
            data.captchaId = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaId').val();
            data.captchaCode = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').val();
        }

        data.confirmValue = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--callphone').val();

        data.callback = function (r) {
            btn.removeClass('preloader');
        };

        that.confirmLastAction(data);
    };


    // показ обратного отсчета до следующего запроса звонка от робота
    BXmakerAuthUserphoneCallConstructor.prototype.showBotCallTimeout = function (timeout) {
        var that = this;
        var btn = that.block.find('.js-baup-get-botcall');

        timeout = (!!timeout ? timeout : 59);

        if (that.timers.botcall) {
            clearInterval(that.timers.botcall);
        }

        // индикатор
        that.timers.botcall = setInterval(function () {
            if (--timeout > 0) {
                btn.text(that.getMessage('bot_call_timeout').replace(/#TIMEOUT#/, timeout));
            } else {
                clearInterval(that.timers.botcall);
                btn.text(that.getMessage('bot_call_request'));
                btn.removeClass("timeout");
            }
        }, 1000);

        //сразу отображаем
        btn.text(that.getMessage('bot_call_timeout').replace(/#TIMEOUT#/, timeout)).addClass('timeout');

    };

    /**
     * Запрос звонка от бота для получения кода
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.startBotCall = function () {
        var that = this;
        var btn = that.block.find('.js-baup-get-botcall');
        var boxLast = that.getBlockByName(that.getLastAction());
        var boxCurrent = that.getBlockByName('botcall');

        if (btn.hasClass('preloader') || btn.hasClass('timeout')) return false;
        btn.addClass('preloader');

        var data = {
            sessid: BX.bitrix_sessid(),
            parameters: that.data.parameters,
            template: that.data.template,
            siteId: that.data.siteId,
            method: 'botCall',
            phone: boxLast.find('input.bxmaker-authuserphone-call-input__field--phone, input.bxmaker-authuserphone-call-input__field--register_phone, input.bxmaker-authuserphone-call-input__field--forget_phone').val()
        };

        //капча
        if (boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').length) {
            data.captchaId = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaId').val();
            data.captchaCode = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').val();
        }

        that.setLastConfirmActionIsRequest();

        $.ajax({
            url: that.data.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (r) {
                that.hideCaptcha();
                btn.removeClass('preloader');
            },
            success: function (r) {
                that.hideCaptcha();

                // событие получения ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': that.data,
                    'request': data,
                    'result': r,
                });


                var timeout = 0;
                that.checkNeedShowCaptcha(r);
                btn.removeClass("preloader");

                if (!!r.response) {
                    var codeField = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--botcode');
                    var description = boxCurrent.find('.bxmaker-authuserphone-call-confirm__description');

                    if (r.response.length && +r.response.length > 0) {
                        codeField.attr('data-length', r.response.length);
                        if (r.response.length > 4) {
                            codeField.attr('placeholder', codeField.attr('data-placeholder').replace(/#/, r.response.length));
                            description.html(description.attr('data-text').replace(/#/, r.response.length));
                        } else {
                            codeField.attr('placeholder', codeField.attr('data-placeholder4').replace(/#/, r.response.length));
                            description.html(description.attr('data-text4').replace(/#/, r.response.length));
                        }
                    } else {
                        codeField.attr('data-length', 6);
                        codeField.attr('placeholder', codeField.attr('data-placeholder').replace(/#/, 6));
                        description.html(description.attr('data-text').replace(/#/, 6));
                    }

                    boxCurrent.find('input.bxmaker-authuserphone-call-input__field--botcode').val('');

                    if (!!r.response.timeout) {
                        that.showBotCallTimeout(r.response.timeout);

                    } else {
                        if (that.timers.usercall) {
                            clearInterval(that.timers.usercall);
                        }
                        btn.text(that.getMessage('bot_call_request')).removeClass("timeout");
                    }


                } else if (!!r.error) {
                    that.showError(r.error.msg);

                    if (!!r.error.more && !!r.error.more.timeout) {
                        that.showBotCallTimeout(r.error.more.timeout);
                    } else {
                        if (that.timers.usercall) {
                            clearInterval(that.timers.usercall);
                        }
                        btn.text(that.getMessage('bot_call_request')).removeClass("timeout");
                    }
                }
            }
        });

    };


    /**
     * Проверка кода из номера бота
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.checkBotCall = function () {
        var that = this;
        var btn = that.block.find('.js-baup-botcall-next');
        var boxCurrent = that.getBlockByName('botcall');
        var data = {
            confirmType: that.getConfirmTypeBotCall()
        };

        if (btn.hasClass('preloader')) return false;
        btn.addClass('preloader');

        //капча
        if (boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').length) {
            data.captchaId = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaId').val();
            data.captchaCode = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').val();
        }

        data.confirmValue = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--botcode').val();

        data.callback = function (r) {
            btn.removeClass('preloader');
        };

        that.confirmLastAction(data);
    };


    // показ обратного отсчета до следующего запроса звонка от робота
    BXmakerAuthUserphoneCallConstructor.prototype.showBotSpeechTimeout = function (timeout) {
        var that = this;
        var btn = that.block.find('.js-baup-get-botspeech');

        timeout = (!!timeout ? timeout : 59);

        if (that.timers.botspeech) {
            clearInterval(that.timers.botspeech);
        }

        // индикатор
        that.timers.botspeech = setInterval(function () {
            if (--timeout > 0) {
                btn.text(that.getMessage('bot_speech_timeout').replace(/#TIMEOUT#/, timeout));
            } else {
                clearInterval(that.timers.botspeech);
                btn.text(that.getMessage('bot_speech_request'));
                btn.removeClass("timeout");
            }
        }, 1000);

        //сразу отображаем
        btn.text(that.getMessage('bot_speech_timeout').replace(/#TIMEOUT#/, timeout)).addClass('timeout');

    };

    /**
     * Запрос звонка от бота для получения кода
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.startBotSpeech = function () {
        var that = this;
        var btn = that.block.find('.js-baup-get-botspeech');
        var boxLast = that.getBlockByName(that.getLastAction());
        var boxCurrent = that.getBlockByName('botspeech');

        if (btn.hasClass('preloader') || btn.hasClass('timeout')) return false;
        btn.addClass('preloader');

        var data = {
            sessid: BX.bitrix_sessid(),
            parameters: that.data.parameters,
            template: that.data.template,
            siteId: that.data.siteId,
            method: 'botSpeech',
            phone: boxLast.find('input.bxmaker-authuserphone-call-input__field--phone, input.bxmaker-authuserphone-call-input__field--register_phone, input.bxmaker-authuserphone-call-input__field--forget_phone').val()
        };

        //капча
        if (boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').length) {
            data.captchaId = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaId').val();
            data.captchaCode = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').val();
        }

        that.setLastConfirmActionIsRequest();

        $.ajax({
            url: that.data.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (r) {
                that.hideCaptcha();
                btn.removeClass('preloader');
            },
            success: function (r) {
                that.hideCaptcha();

                // событие получения ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': that.data,
                    'request': data,
                    'result': r,
                });


                var timeout = 0;
                that.checkNeedShowCaptcha(r);
                btn.removeClass("preloader");

                if (!!r.response) {
                    var codeField = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--botspeech');

                    if (r.response.length && +r.response.length > 0) {
                        codeField.attr('data-length', r.response.length);

                    } else {
                        codeField.attr('data-length', 6);
                    }

                    boxCurrent.find('input.bxmaker-authuserphone-call-input__field--botspeech').val('');

                    if (!!r.response.timeout) {
                        that.showBotSpeechTimeout(r.response.timeout);

                    } else {
                        if (that.timers.usercall) {
                            clearInterval(that.timers.usercall);
                        }
                        btn.text(that.getMessage('bot_speech_request')).removeClass("timeout");
                    }


                } else if (!!r.error) {
                    that.showError(r.error.msg);

                    if (!!r.error.more && !!r.error.more.timeout) {
                        that.showBotSpeechTimeout(r.error.more.timeout);
                    } else {
                        if (that.timers.usercall) {
                            clearInterval(that.timers.usercall);
                        }
                        btn.text(that.getMessage('bot_speech_request')).removeClass("timeout");
                    }
                }
            }
        });

    };


    /**
     * Проверка кода из номера бота
     * @returns {boolean}
     */
    BXmakerAuthUserphoneCallConstructor.prototype.checkBotSpeech = function () {
        var that = this;
        var btn = that.block.find('.js-baup-botspeech-next');
        var boxCurrent = that.getBlockByName('botspeech');
        var data = {
            confirmType: that.getConfirmTypeBotSpeech()
        };

        if (btn.hasClass('preloader')) return false;
        btn.addClass('preloader');

        //капча
        if (boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').length) {
            data.captchaId = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaId').val();
            data.captchaCode = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--captchaCode').val();
        }

        data.confirmValue = boxCurrent.find('input.bxmaker-authuserphone-call-input__field--botspeech').val();

        data.callback = function (r) {
            btn.removeClass('preloader');
        };


        that.confirmLastAction(data);
    };


    /**
     * Подтверждение последнего действия, которое требует подтверждения
     * @param params
     */
    BXmakerAuthUserphoneCallConstructor.prototype.confirmLastAction = function (params) {
        var that = this;

        that.setLastConfirmActionIsCheck();

        switch (that.getLastAction()) {
            case 'auth': {
                that.startAuthEnter(params);
                break;
            }
            case 'register': {
                that.startRegisterEnter(params);
                break;
            }
            case 'forget': {
                that.startForgotEnter(params);
                break;
            }
            default: {

                break;
            }
        }
    };

    /**
     * Когда поле для символов капчи заполнено
     */
    BXmakerAuthUserphoneCallConstructor.prototype.onCaptchaComplete = function () {
        var that = this;

        if (this.lastConfirmActionIsRequest()) {
            // нажимаем на кнопку получить код, запросить звонок и тп в активном блоке
            that.getActiveBlock().find('.js-baup-confirm-request').click();
        } else {
            // нажимаем на кнопку продолжить в активном блоке
            that.getActiveBlock().find('.js-baup-continue').click();
        }

    };

    /**
     * Последняя опреация при попытке подтвердить была - запрос подтвреждения
     */
    BXmakerAuthUserphoneCallConstructor.prototype.setLastConfirmActionIsRequest = function () {
        this.lastConfirmAction = 'request';
    }

    /**
     * Последняя опреация при попытке подтвердить была - запрос проверки
     */
    BXmakerAuthUserphoneCallConstructor.prototype.setLastConfirmActionIsCheck = function () {
        this.lastConfirmAction = 'check';
    }

    /**
     * Проверка была ли последняя операция подтвреждения номера запросом на проверку
     */
    BXmakerAuthUserphoneCallConstructor.prototype.lastConfirmActionIsRequest = function () {
        return this.lastConfirmAction === 'request';
    }

    /**
     * После того как польвзаотель успешно авторизовался, скроем ненужные блоки
     */
    BXmakerAuthUserphoneCallConstructor.prototype.onAuth = function () {
        this.hideBlocks();
    }


}


if (!window.BXmakerAuthUserphoneCallWorker) {
    function BXmakerAuthUserphoneCallWorker() {
        window.BXmakerAuthUserphoneCall = window.BXmakerAuthUserphoneCall || {};
        $('.bxmaker-authuserphone-call:not(.inited)').each(function () {
            var block = $(this);
            var rand = block.attr('data-rand');

            if (!!block && !!window.BXmakerAuthUserphoneCall[rand]) return false;
            window.BXmakerAuthUserphoneCall[rand] = new BXmakerAuthUserphoneCallConstructor(block);
            window.BXmakerAuthUserphoneCall[rand].init();
        });
    }
}

if (window.frameCacheVars !== undefined && !!window.frameCacheVars.AUTO_UPDATE) {
    if (!window.BXmakerAuthUserphoneCallWorker_onFrame) {
        window.BXmakerAuthUserphoneCallWorker_onFrame = true;
        BX.addCustomEvent("onFrameDataReceived", BXmakerAuthUserphoneCallWorker);
    } else {
        BX.ready(BXmakerAuthUserphoneCallWorker);
    }

} else {
    BX.ready(BXmakerAuthUserphoneCallWorker);
}

