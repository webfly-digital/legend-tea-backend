/**
 *  ласс дл€ работы комопнента авторизации регистрации
 * @param b
 * @param $
 *
 * @returns {boolean}
 *
 * @emits bxmaker.authuserphone.ajax {request, result, params} - событие вызываетс€ после получени€ ответа на ajax запрос (авторизаци€, регистраци€ и тп)
 *
 * @constructor
 */
function BXmakerAuthUserphone(b, $) {
    if (b == undefined || b.hasClass('js_init_complete')) {
        return false;
    }

    var self = this, box = b, msgBox = box.find('.bxmaker-authuserphone-login-msg');
    var control = false;
    var rand = box.attr('data-rand');
    var paramsData = (!!window.BXmakerAuthUserPhoneLoginData && !!window.BXmakerAuthUserPhoneLoginData[rand] ? window.BXmakerAuthUserPhoneLoginData[rand] : false);

    // console.log('constructor',rand , window.BXmakerAuthUserPhoneLoginData);

    if (!paramsData) {
        return false;
    }

    box.addClass('js_init_complete');

    self._authPhoneNumber = null;
    self._authPhoneNumberIsInit = false;
    self._authPhoneNumberIsSet = false;

    self._regPhoneNumber = null;
    self._regPhoneNumberIsInit = false;
    self._regPhoneNumberIsSet = false;


    if (!!BX.UserConsent) {
        control = BX.UserConsent.load(BX(box.attr('id')));

        BX.addCustomEvent(
            control,
            BX.UserConsent.events.save,
            function (data) {

                self.save({
                    'consent': 1,
                    'consent_id': data.id,
                    'consent_sec': data.sec,
                    'consent_url': data.url
                }, box.find(".bxmaker-authuserphone-login-btn"));
            }
        );
    }

    self.checkPhoneCountryPopupPosition = function () {
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

    self.onAjaxSuccess = function (data, request) {
        if (!request || !request.url || !request.url.match(/action=main\.phonenumber\.getCountries$/)) {
            return true;
        }
        this.checkPhoneCountryPopupPosition();
    };

    BX.addCustomEvent('onAjaxSuccess', BX.proxy(self.onAjaxSuccess, self));

    self.isEnabledPhoneMask = function(){
        return paramsData.phoneMaskParams && paramsData.phoneMaskParams.type && paramsData.phoneMaskParams.type === 'bitrix';
    }

    //  маска дл€ авторизации
    if (self.isEnabledPhoneMask() && !paramsData.isEnabledAuthByLogin && !paramsData.isEnabledAuthByEmail) {

        var authPhoneInput = box.find('input[name="phone"]');
        if (authPhoneInput.length) {

            self._authPhoneNumber = new BX.PhoneNumber.Input({
                node: authPhoneInput.get(0),
                flagNode: box.find('.bxmaker-authuserphone-login-row__flag--auth').get(0),
                flagSize: 16, // –азмер флага [16, 24, 32]
                defaultCountry: paramsData.phoneMaskParams.defaultCountry, // —трана по-умолчанию
                countryTopList: paramsData.phoneMaskParams.countryTopList,
                onInitialize: function (e) {
                    setTimeout(function () {
                        self._authPhoneNumber.formatter.replaceCountry(paramsData.phoneMaskParams.defaultCountry);
                        self._authPhoneNumberIsInit = true;
                    }, 50);
                },
                onChange: function (data) {
                    authPhoneInput.focus();
                }
            });
            authPhoneInput.on("focus", function (e) {
                var input = $(this);
                if (self._authPhoneNumberIsInit && !self._authPhoneNumberIsSet) {
                    self._authPhoneNumberIsSet = true;
                    if (!input.val().length) {
                        input.val(self._authPhoneNumber.formatter.getFormattedNumber());
                    }
                }
            });

            box.find('.bxmaker-authuserphone-login-row__flag--auth').on("click", function(){
                self.checkPhoneCountryPopupPosition();
            });
        }

    }


    // маска дл€ регистрации
    if (self.isEnabledPhoneMask()) {
        var regPhoneInput = box.find('input[name="register_phone"]');
        if (regPhoneInput.length) {
            self._regPhoneNumber = new BX.PhoneNumber.Input({
                node: regPhoneInput.get(0),
                flagNode: box.find('.bxmaker-authuserphone-login-row__flag--reg').get(0),
                flagSize: 16, // –азмер флага [16, 24, 32]
                defaultCountry: paramsData.phoneMaskParams.defaultCountry, // —трана по-умолчанию
                countryTopList: paramsData.phoneMaskParams.countryTopList,
                onInitialize: function (e) {
                    setTimeout(function () {
                        self._regPhoneNumber.formatter.replaceCountry(paramsData.phoneMaskParams.defaultCountry);
                        self._regPhoneNumberIsInit = true;
                    }, 50);
                },
                onChange: function (data) {
                    regPhoneInput.focus();
                }
            });
            regPhoneInput.on("focus", function (e) {
                var input = $(this);
                if (self._regPhoneNumberIsInit && !self._regPhoneNumberIsSet) {
                    self._regPhoneNumberIsSet = true;
                    if (!input.val().length) {
                        input.val(self._regPhoneNumber.formatter.getFormattedNumber());
                    }
                }
            });

            box.find('.bxmaker-authuserphone-login-row__flag--reg').on("click", function(){
                self.checkPhoneCountryPopupPosition();
            });
        }
    }


    self.getMessage = function (name) {
        return ((!!paramsData.messages && !!paramsData.messages[name]) ? paramsData.messages[name] : '');
    };





    // show errors and messages
    self.showMsg = function (msg, error) {
        var msg = msg || null,
            error = error || false;

        if (!self.hideMsg()) {
            return false;
        }
        if (msg) {
            if (error) msgBox.addClass('bxmaker-authuserphone-login-msg--error').html(msg);
            else msgBox.addClass('bxmaker-authuserphone-login-msg--success').html(msg);
        }
    };

    self.hideMsg = function (r) {
        if (!!msgBox === false) return false;
        msgBox.removeClass('bxmaker-authuserphone-login-msg--error bxmaker-authuserphone-login-msg--success').empty();
        return true;
    };

    // показываем капчу
    self.showCaptcha = function (param) {
        var cb = box.find('.bxmaker-authuserphone-login-captcha');

        param.captchaId = param.captchaId || '';
        param.captchaSrc = param.captchaSrc || '';

        if (!cb.find('input[name="captchaId"]').length) {
            var html = '<input type="hidden" name="captchaId" value="' + param.captchaId + '"/>' +
                '<img src="' + param.captchaSrc + '" title="' + self.getMessage('UPDATE_CAPTCHA_IMAGE') + '" alt=""/>' +
                '<span class="bxmaker-authuserphone-login-captcha__reload" title="' + self.getMessage('UPDATE_CAPTCHA_IMAGE') + '"></span>' +
                '<input type="text" name="captchaCode" class="captchaCode" placeholder="' + self.getMessage('INPUT_CAPTHCA') + '"/>';

            cb.append(html);
        } else {
            cb.find('input[name="captchaId"]').val(param.captchaId);
            cb.find('img').attr('src', param.captchaSrc);
        }

        cb.fadeIn(300);
    };

    //скрытие блока с капчей
    self.hideCaptcha = function () {
        box.find('.bxmaker-authuserphone-login-captcha').empty().hide();
    };

    self.showPasswordText = function () {
        var btn = box.find('.bxmaker-authuserphone-login__show-password');
        if (!self.isShowPasswordText()) {
            btn.addClass('active').attr('title', btn.attr('data-title-hide'));
            btn.parent().find('input[type="password"]').prop('type', 'text');
        }
    };
    self.hidePasswordText = function () {
        var btn = box.find('.bxmaker-authuserphone-login__show-password');
        if (self.isShowPasswordText()) {
            btn.removeClass('active').attr('title', btn.attr('data-title-show'));
            btn.parent().find('input[type="text"]').prop('type', 'password');
        }
    };
    self.isShowPasswordText = function () {
        return box.find('.bxmaker-authuserphone-login__show-password').hasClass("active");
    };


    //пока регистрации
    self.showReg = function () {

        var btn = $(this);
        var btn_inter = box.find('.btn_box .bxmaker-authuserphone-login-btn');
        var pass_input = box.find('input[name="password"]');


        self.hideMsg();
        self.hidePasswordText();


        // self.showMsg(self.getMessage('REGISTER_INFO'));
        box.removeClass('register_show bxmaker-authuserphone-login--auth');
        box.addClass('register_show bxmaker-authuserphone-login--reg');


        btn_inter.text(btn_inter.attr('data-reg-title'));

        pass_input.attr('placeholder', pass_input.attr('data-reg'));

        box.find('.bxmaker-authuserphone-login-row--registration').show();

    };

    // показ авторизации
    self.showAuth = function () {
        var btn_inter = box.find('.btn_box .bxmaker-authuserphone-login-btn');
        var pass_input = box.find('input[name="password"]');


        self.hideMsg();
        self.hidePasswordText();


        self.showMsg(null);
        box.addClass('register_show bxmaker-authuserphone-login--auth');
        box.removeClass('register_show bxmaker-authuserphone-login--reg');


        btn_inter.text(btn_inter.attr('data-auth-title'));

        pass_input.attr('placeholder', pass_input.attr('data-auth'));

        box.find('.bxmaker-authuserphone-login-row--registration').hide();
    };


    // отправка данных - вход или регистраци€
    self.save = function (data, btn) {
        var data = data || {};

        btn.addClass("preloader");

        data['parameters'] = paramsData['parameters'];
        data['template'] = paramsData['template'];
        data['siteId'] = paramsData['siteId'];

        data['sessid'] = BX.bitrix_sessid();

        data['method'] = (box.hasClass('register_show') ? 'register' : 'auth');

        data['email'] = box.find('input[name="email"]').val();
        data['login'] = box.find('input[name="login"]').val();
        data['password'] = box.find('input[name="password"]').val();
        data['passwordOrSmsCode'] = box.find('input[name="password_sms_code"]').val();
        data['smsCode'] = box.find('input[name="sms_code"]').val();
        data['captchaId'] = box.find('input[name="captchaId"]').val();
        data['captchaCode'] = box.find('input[name="captchaCode"]').val();


        if (box.hasClass('register_show')) {
            data['method'] = 'register';
            data['phone'] = box.find('input[name="register_phone"]').val();
        } else {
            data['method'] = 'auth';
            data['phone'] = box.find('input[name="phone"]').val();
        }


        self.hideCaptcha();

        $.ajax({
            url: paramsData['ajaxUrl'],
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (r) {
                self.showMsg('Error connect to server!', true);
                btn.removeClass("preloader");
            },
            success: function (r) {

                // событие получени€ ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': paramsData,
                    'request': data,
                    'result': r,
                });

                btn.removeClass("preloader");

                if (!!r.response) {
                    self.showMsg(r.response.msg);
                    console.log('bxmaker.authuserphone success ', r);

                    setTimeout(function () {
                        if (!!r.response.redirect) {
                            location.href = r.response.redirect;
                        } else if (!!r.response.reload) {
                            location.reload();
                        }
                    }, 1);

                    //удал€ем ве пол€
                    box.find('.bxmaker-authuserphone-login-row').remove();

                    // удал€ем кнопку перегключени€ на регистрацию
                    box.find('.bxmaker-authuserphone-login__change-form').remove();

                } else if (!!r.error) {

                    if (!!r.error && r.error.code === 'INVALID_SESSID' && r.error.more && r.error.more.sessid) {
                        BX.message({"bitrix_sessid": r.error.more.sessid});
                        return false;
                    }

                    self.showMsg(r.error.msg, true);

                    //captcha
                    if (!!r.error.more.captchaId) {
                        self.showCaptcha(r.error.more);
                    }
                }

                // событие получени€ ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'request': data,
                    'result': r,
                    'params': paramsData
                });


            }
        });
    };


    //registr
    box.on("click", '.bxmaker-authuserphone-login__change-form', function () {
        if (box.hasClass('register_show')) {
            self.showAuth();
        } else {
            self.showReg();
        }
    });


    // btn show password
    box.on("click", '.bxmaker-authuserphone-login__show-password', function () {
        if (self.isShowPasswordText()) {
            self.hidePasswordText();
        } else {
            self.showPasswordText();
        }
    });

    // btn enter
    box.find(".bxmaker-authuserphone-login-btn").on("click", function () {
        var btn = $(this);
        var bRegister = false;

        if (btn.hasClass("preloader")) return false;

        self.hideMsg();

        if (box.hasClass('register_show') && box.attr('data-consent') == 'Y') {
            BX.onCustomEvent('bxmaker-authuserphone-login__consent--' + rand, []);
        } else {
            self.save({}, btn);
        }
    });

    // btn send code
    box.on("click", '.bxmaker-authuserphone-login-link', function () {
        var btn = $(this);

        if (btn.hasClass('preloader') || btn.hasClass('timeout')) return false;
        btn.addClass('preloader');

        var data = {};
        data['parameters'] = paramsData['parameters'];
        data['template'] = paramsData['template'];
        data['siteId'] = paramsData['siteId'];
        data['sessid'] = BX.bitrix_sessid();

        data['method'] = 'sendCode';

        if (box.hasClass('register_show')) {
            data['registration'] = 'Y';
            data['phone'] = box.find('input[name="register_phone"]').val();
        } else {
            data['registration'] = 'N';
            data['phone'] = box.find('input[name="phone"]').val();
        }


        //мен€ем тип пол€ с паролем, чтобы подставл€лс€ код из смс на iphone
        self.showPasswordText();
        box.find('input[name="password"]').val('').focus();

        data['captchaId'] = box.find('input[name="captchaId"]').val();
        data['captchaCode'] = box.find('input[name="captchaCode"]').val();

        self.hideCaptcha();

        self.hideMsg();

        $.ajax({
            url: paramsData['ajaxUrl'],
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (e) {
                self.showMsg('Error connect to server!', true);
                btn.removeClass("preloader");
            },
            success: function (r) {
                var timeout = 0;

                // событие получени€ ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': paramsData,
                    'request': data,
                    'result': r,
                });

                if (!!r.response) {
                    self.showMsg(r.response.msg);

                    btn.removeClass("preloader");

                    if (r.response.length) {
                        box.find('input[name="password"]').attr('data-length', r.response.length);
                    }

                    if (!!r.response.timeout) {
                        timeout = (!!r.response.timeout ? r.response.timeout : 59);

                        // индикатор
                        var smsInterval = setInterval(function () {
                            if (--timeout > 0) {
                                btn.text(self.getMessage('BTN_SEND_CODE_TIMEOUT').replace(/#TIMEOUT#/, timeout));
                            } else {
                                clearInterval(smsInterval);
                                btn.text(self.getMessage('BTN_SEND_CODE'));
                                btn.removeClass("timeout");
                            }
                        }, 1000);

                        //сразу отображаем
                        btn.text(self.getMessage('BTN_SEND_CODE_TIMEOUT').replace(/#TIMEOUT#/, timeout)).addClass('timeout');
                    }
                } else if (!!r.error) {
                    btn.removeClass("preloader");

                    if (!!r.error && r.error.code == 'INVALID_SESSID' && r.error.more && r.error.more.sessid) {
                        BX.message({"bitrix_sessid": r.error.more.sessid});
                        return false;
                    }

                    self.showMsg(r.error.msg, true);

                    //captcha
                    if (!!r.error.more.captchaId) {
                        self.showCaptcha(r.error.more);
                    }

                    if (!!r.error.more && !!r.error.more.timeout) {

                        timeout = r.error.more.timeout;

                        var smsInterval = setInterval(function () {
                            if (--timeout > 0) {
                                btn.text(self.getMessage('BTN_SEND_CODE_TIMEOUT').replace(/#TIMEOUT#/, timeout));
                            } else {
                                clearInterval(smsInterval);
                                btn.text(self.getMessage('BTN_SEND_CODE'));
                                btn.removeClass("timeout");
                            }
                        }, 1000);

                        btn.text(self.getMessage('BTN_SEND_CODE_TIMEOUT').replace(/#TIMEOUT#/, timeout)).removeClass("preloader").addClass('timeout');
                    }
                } else {
                    btn.removeClass("preloader");
                }


            }
        })
    });

    // btn send emil
    box.on("click", '.bxmaker-authuserphone-login-btn__send-email', function () {
        var btn = $(this);

        if (btn.hasClass('preloader')) return false;
        btn.addClass('preloader');

        self.hideMsg();

        var data = {
            method: 'sendEmail',
            phone: box.find('input[name="phone"]').val()
        };

        data['parameters'] = paramsData['parameters'];
        data['template'] = paramsData['template'];
        data['siteId'] = paramsData['siteId'];
        data['sessid'] = BX.bitrix_sessid();


        if (box.find('input[name="captchaId"]').length) {
            data['captchaId'] = box.find('input[name="captchaId"]').val();
            data['captchaCode'] = box.find('input[name="captchaCode"]').val();
        }

        $.ajax({
            url: paramsData['ajaxUrl'],
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (r) {
                self.showMsg('Error connect to server!', true);
                btn.removeClass("preloader");
            },
            success: function (r) {

                // событие получени€ ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': paramsData,
                    'request': data,
                    'result': r,
                });


                if (!!r.response) {
                    self.showMsg(r.response.msg);
                    btn.removeClass("preloader").hide();
                } else if (!!r.error) {
                    btn.removeClass("preloader");

                    if (!!r.error && r.error.code == 'INVALID_SESSID' && r.error.more && r.error.more.sessid) {
                        BX.message({"bitrix_sessid": r.error.more.sessid});
                        return false;
                    }

                    self.showMsg(r.error.msg, true);

                    //captcha
                    if (!!r.error.more.captchaId) {
                        self.showCaptcha(r.error.more);
                    }

                } else {
                    btn.removeClass("preloader");
                }

                // событие получени€ ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'request': data,
                    'result': r,
                    'params': paramsData
                });
            }
        })
    });

    // обновление капчи
    box.on("click", '.bxmaker-authuserphone-login-captcha img, .bxmaker-authuserphone-login-captcha__reload', function () {
        var b = box.find('.bxmaker-authuserphone-login-captcha');

        if (b.hasClass("preloader")) return false;
        b.addClass("preloader");


        var data = {};
        data['parameters'] = paramsData['parameters'];
        data['template'] = paramsData['template'];
        data['siteId'] = paramsData['siteId'];
        data['sessid'] = BX.bitrix_sessid();
        data['method'] = 'refreshCaptcha';


        $.ajax({
            url: paramsData['ajaxUrl'],
            type: 'POST',
            dataType: 'json',
            data: data,
            error: function (r) {
                self.showMsg('Error connect to server!', true);
                b.removeClass("preloader");
            },
            success: function (r) {


                // событие получени€ ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'params': paramsData,
                    'request': data,
                    'result': r,
                });

                b.removeClass("preloader");

                if (!!r.response) {
                    self.showCaptcha(r.response);

                } else if (!!r.error) {
                    if (!!r.error && r.error.code == 'INVALID_SESSID' && r.error.more && r.error.more.sessid) {
                        BX.message({"bitrix_sessid": r.error.more.sessid});
                        return false;
                    }
                }

                // событие получени€ ответа на ajax запрос
                $(document).trigger('bxmaker.authuserphone.ajax', {
                    'request': data,
                    'result': r,
                    'params': paramsData
                });

            }
        });
    });

    // отправка при клике по кнопке enter
    box.on("keyup", "input", function (e) {
        if (e.keyCode == 13) {
            box.find(".bxmaker-authuserphone-login-btn").click();
        }
    });

    box.find('input[name="password"]').on("keyup", function (e) {
        let input = $(this);
        if (input.attr('data-length') && +input.attr('data-length') > 0 && input.val().trim().length == +input.attr('data-length')) {
            box.find(".bxmaker-authuserphone-login-btn").click();
        }
    });


    box.find('.btn_logout').attr('href', location.pathname + (location.search.length > 0 ? location.search + '&' : '?') + 'logout=Y');

    if ((location.hash === "#reg" || location.hash === "#registration") && paramsData.isEnabledRegister) {
        self.showReg();
    }


}


// воркер --
if (!window.BXmakerAuthUserphoneLoginWorker) {
    function BXmakerAuthUserphoneLoginWorker() {

        if (!!window.jQuery == false) {
            console.log('bxmaker.authuserphone.login - need jQuery');
            return true;
        }

        window.$ = window.jQuery;

        jQuery(document).ready(function () {
            $('.bxmaker-authuserphone-login').each(function () {
                new BXmakerAuthUserphone(jQuery(this), jQuery);
            })
        });
    }
}

//запуск ----
if (window.frameCacheVars !== undefined && !!window.frameCacheVars.AUTO_UPDATE) {

    if (!window.BXmakerAuthUserphoneLoginWorker_onFrame) {
        window.BXmakerAuthUserphoneLoginWorker_onFrame = true;

        BX.addCustomEvent("onFrameDataReceived", function (json) {
            setTimeout(function () {
                BXmakerAuthUserphoneLoginWorker();
            }, 200);
        });
        BX.addCustomEvent("onFrameDataRequestFail", function (json) {
            setTimeout(function () {
                BXmakerAuthUserphoneLoginWorker();
            }, 200);
        });
    } else {
        BX.ready(function () {
            setTimeout(function () {
                BXmakerAuthUserphoneLoginWorker();
            }, 200);
        });
    }

} else {
    BX.ready(function () {
        setTimeout(function () {
            BXmakerAuthUserphoneLoginWorker();
        }, 200);
    });
}



