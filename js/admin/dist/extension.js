'use strict';

System.register('migratetoflarum/redirects/components/RedirectsSettingsModal', ['flarum/app', 'flarum/components/SettingsModal', 'flarum/components/Switch', 'flarum/components/Select', 'flarum/components/Button', 'flarum/helpers/icon'], function (_export, _context) {
    "use strict";

    var app, SettingsModal, Switch, Select, Button, icon, settingsPrefix, translationPrefix, DEFAULT_ENABLED_WHEN_NOT_SET, DEFAULT_ACTIVE_WHEN_NOT_SET, DEFAULT_EXTERNAL_WHEN_NOT_SET, DEFAULT_TYPE_WHEN_NOT_SET, RedirectsSettingsModal;
    return {
        setters: [function (_flarumApp) {
            app = _flarumApp.default;
        }, function (_flarumComponentsSettingsModal) {
            SettingsModal = _flarumComponentsSettingsModal.default;
        }, function (_flarumComponentsSwitch) {
            Switch = _flarumComponentsSwitch.default;
        }, function (_flarumComponentsSelect) {
            Select = _flarumComponentsSelect.default;
        }, function (_flarumComponentsButton) {
            Button = _flarumComponentsButton.default;
        }, function (_flarumHelpersIcon) {
            icon = _flarumHelpersIcon.default;
        }],
        execute: function () {
            settingsPrefix = 'migratetoflarum-redirects.';
            translationPrefix = 'migratetoflarum-redirects.admin.settings.';
            DEFAULT_ENABLED_WHEN_NOT_SET = false;
            DEFAULT_ACTIVE_WHEN_NOT_SET = false;
            DEFAULT_EXTERNAL_WHEN_NOT_SET = false;
            DEFAULT_TYPE_WHEN_NOT_SET = 301;

            RedirectsSettingsModal = function (_SettingsModal) {
                babelHelpers.inherits(RedirectsSettingsModal, _SettingsModal);

                function RedirectsSettingsModal() {
                    babelHelpers.classCallCheck(this, RedirectsSettingsModal);
                    return babelHelpers.possibleConstructorReturn(this, (RedirectsSettingsModal.__proto__ || Object.getPrototypeOf(RedirectsSettingsModal)).apply(this, arguments));
                }

                babelHelpers.createClass(RedirectsSettingsModal, [{
                    key: 'title',
                    value: function title() {
                        return app.translator.trans(translationPrefix + 'title');
                    }
                }, {
                    key: 'readRules',
                    value: function readRules() {
                        var rules = [];

                        try {
                            rules = JSON.parse(this.setting(settingsPrefix + 'rules')());
                        } catch (e) {
                            rules = [];
                        }

                        return rules;
                    }
                }, {
                    key: 'config',
                    value: function config() {
                        var _this2 = this;

                        this.$('.js-redirects-sortable').sortable({
                            handle: '.js-redirects-handle'
                        }).on('sortupdate', function () {
                            var rules = _this2.readRules();

                            var newRules = [];

                            var sorting = _this2.$('.js-redirects-entry').each(function () {
                                newRules.push(rules[$(this).data('index')]);
                            });

                            _this2.updateRules(sorting);
                        });
                    }
                }, {
                    key: 'form',
                    value: function form() {
                        var _this3 = this;

                        var rules = this.readRules();

                        return m('.RedirectSettings', [m('p', app.translator.trans(translationPrefix + 'description', {
                            a: m('a', {
                                href: 'https://github.com/migratetoflarum/redirects',
                                target: '_blank'
                            })
                        })), m('p', Button.component({
                            className: 'Button Button--primary',
                            onclick: function onclick() {
                                rules.push({});

                                _this3.updateRules(rules);
                            },
                            children: app.translator.trans(translationPrefix + 'control.add')
                        })), m('.js-redirects-sortable', rules.map(function (rule, index) {
                            return m('.RedirectRule.js-redirects-entry', {
                                'data-index': index
                            }, [m('.RedirectRule-handle.js-redirects-handle', icon('arrows')), m('.RedirectRule-settings', [m('.RedirectRule-name', app.translator.trans(translationPrefix + 'name.rule', {
                                number: index + 1
                            })), m('.RedirectRule-row', [m('.Form-group', [m('label', app.translator.trans(translationPrefix + 'field.condition')), m('input.FormControl', {
                                value: rule.condition || '',
                                onchange: m.withAttr('value', function (value) {
                                    rule.condition = value;

                                    _this3.updateRules(rules);
                                })
                            })]), m('.Form-group', [m('label', app.translator.trans(translationPrefix + 'field.redirect')), m('input.FormControl', {
                                value: rule.redirect || '',
                                onchange: m.withAttr('value', function (value) {
                                    rule.redirect = value;

                                    _this3.updateRules(rules);
                                })
                            })])]), m('.RedirectRule-row', [m('.Form-group', [Switch.component({
                                state: rule.active || DEFAULT_ACTIVE_WHEN_NOT_SET,
                                onchange: function onchange(value) {
                                    rule.active = value;

                                    _this3.updateRules(rules);
                                },
                                children: app.translator.trans(translationPrefix + 'field.active')
                            }), Switch.component({
                                state: rule.external || DEFAULT_EXTERNAL_WHEN_NOT_SET,
                                onchange: function onchange(value) {
                                    rule.external = value;

                                    _this3.updateRules(rules);
                                },
                                children: app.translator.trans(translationPrefix + 'field.external')
                            })]), m('.Form-group', [m('label', app.translator.trans(translationPrefix + 'field.type')), Select.component({
                                options: {
                                    301: app.translator.trans(translationPrefix + 'option.301'),
                                    302: app.translator.trans(translationPrefix + 'option.302')
                                },
                                value: rule.type || DEFAULT_TYPE_WHEN_NOT_SET,
                                onchange: function onchange(value) {
                                    rule.type = parseInt(value);

                                    _this3.updateRules(rules);
                                }
                            })])]), m('.RedirectRule-row', [m('.Form-group', Switch.component({
                                state: rule.enabled || DEFAULT_ENABLED_WHEN_NOT_SET,
                                onchange: function onchange(value) {
                                    rule.enabled = value;

                                    _this3.updateRules(rules);
                                },
                                disabled: !rule.condition || !rule.redirect,
                                children: app.translator.trans(translationPrefix + 'field.enabled')
                            })), m('.Form-group', Button.component({
                                icon: 'trash',
                                className: 'Button Button--danger',
                                onclick: function onclick() {
                                    rules.splice(index, 1);

                                    _this3.updateRules(rules);
                                },
                                children: app.translator.trans(translationPrefix + 'control.delete')
                            }))])])]);
                        }))]);
                    }
                }, {
                    key: 'updateRules',
                    value: function updateRules(rules) {
                        var toSave = rules.map(function (rule) {
                            if (rule.active === DEFAULT_ENABLED_WHEN_NOT_SET) {
                                delete rule.enabled;
                            }

                            if (rule.active === DEFAULT_ACTIVE_WHEN_NOT_SET) {
                                delete rule.active;
                            }

                            if (rule.external === DEFAULT_EXTERNAL_WHEN_NOT_SET) {
                                delete rule.external;
                            }

                            if (rule.type === DEFAULT_TYPE_WHEN_NOT_SET) {
                                delete rule.type;
                            }

                            if (!rule.condition || !rule.redirect) {
                                rule.enabled = false;
                            }

                            return rule;
                        });

                        this.setting(settingsPrefix + 'rules')(JSON.stringify(toSave));
                    }
                }]);
                return RedirectsSettingsModal;
            }(SettingsModal);

            _export('default', RedirectsSettingsModal);
        }
    };
});;
'use strict';

System.register('migratetoflarum/redirects/main', ['flarum/extend', 'flarum/app', 'migratetoflarum/redirects/components/RedirectsSettingsModal'], function (_export, _context) {
    "use strict";

    var extend, app, RedirectsSettingsModal;
    return {
        setters: [function (_flarumExtend) {
            extend = _flarumExtend.extend;
        }, function (_flarumApp) {
            app = _flarumApp.default;
        }, function (_migratetoflarumRedirectsComponentsRedirectsSettingsModal) {
            RedirectsSettingsModal = _migratetoflarumRedirectsComponentsRedirectsSettingsModal.default;
        }],
        execute: function () {

            app.initializers.add('migratetoflarum-redirects', function (app) {
                app.extensionSettings['migratetoflarum-redirects'] = function () {
                    return app.modal.show(new RedirectsSettingsModal());
                };
            });
        }
    };
});