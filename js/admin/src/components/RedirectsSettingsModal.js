import app from 'flarum/app';
import SettingsModal from 'flarum/components/SettingsModal';
import Switch from 'flarum/components/Switch';
import Select from 'flarum/components/Select';
import Button from 'flarum/components/Button';
import icon from 'flarum/helpers/icon';

const settingsPrefix = 'migratetoflarum-redirects.';
const translationPrefix = 'migratetoflarum-redirects.admin.settings.';
const DEFAULT_ENABLED_WHEN_NOT_SET = false;
const DEFAULT_ACTIVE_WHEN_NOT_SET = false;
const DEFAULT_EXTERNAL_WHEN_NOT_SET = false;
const DEFAULT_TYPE_WHEN_NOT_SET = 301;

export default class RedirectsSettingsModal extends SettingsModal {
    title() {
        return app.translator.trans(translationPrefix + 'title');
    }

    readRules() {
        let rules = [];

        try {
            rules = JSON.parse(this.setting(settingsPrefix + 'rules')());
        } catch (e) {
            rules = [];
        }

        return rules;
    }

    config() {
        this.$('.js-redirects-sortable')
            .sortable({
                handle: '.js-redirects-handle',
            })
            .on('sortupdate', () => {
                const rules = this.readRules();

                let newRules = [];

                const sorting = this.$('.js-redirects-entry')
                    .each(function () {
                        newRules.push(rules[$(this).data('index')]);
                    });

                this.updateRules(sorting);
            });
    }

    form() {
        const rules = this.readRules();

        return m('.RedirectSettings', [
            m('p', app.translator.trans(translationPrefix + 'description', {
                a: m('a', {
                    href: 'https://github.com/migratetoflarum/redirects',
                    target: '_blank',
                }),
            })),
            m('p', Button.component({
                className: 'Button Button--primary',
                onclick: () => {
                    rules.push({});

                    this.updateRules(rules);
                },
                children: app.translator.trans(translationPrefix + 'control.add'),
            })),
            m('.js-redirects-sortable', rules.map(
                (rule, index) => m('.RedirectRule.js-redirects-entry', {
                    'data-index': index,
                }, [
                    m('.RedirectRule-handle.js-redirects-handle', icon('arrows')),
                    m('.RedirectRule-settings', [
                        m('.RedirectRule-name', app.translator.trans(translationPrefix + 'name.rule', {
                            number: index + 1,
                        })),
                        m('.RedirectRule-row', [
                            m('.Form-group', [
                                m('label', app.translator.trans(translationPrefix + 'field.condition')),
                                m('input.FormControl', {
                                    value: rule.condition || '',
                                    onchange: m.withAttr('value', value => {
                                        rule.condition = value;

                                        this.updateRules(rules);
                                    }),
                                }),
                            ]),
                            m('.Form-group', [
                                m('label', app.translator.trans(translationPrefix + 'field.redirect')),
                                m('input.FormControl', {
                                    value: rule.redirect || '',
                                    onchange: m.withAttr('value', value => {
                                        rule.redirect = value;

                                        this.updateRules(rules);
                                    }),
                                }),
                            ]),
                        ]),
                        m('.RedirectRule-row', [
                            m('.Form-group', [
                                Switch.component({
                                    state: rule.active || DEFAULT_ACTIVE_WHEN_NOT_SET,
                                    onchange: value => {
                                        rule.active = value;

                                        this.updateRules(rules);
                                    },
                                    children: app.translator.trans(translationPrefix + 'field.active'),
                                }),
                                Switch.component({
                                    state: rule.external || DEFAULT_EXTERNAL_WHEN_NOT_SET,
                                    onchange: value => {
                                        rule.external = value;

                                        this.updateRules(rules);
                                    },
                                    children: app.translator.trans(translationPrefix + 'field.external'),
                                }),
                            ]),
                            m('.Form-group', [
                                m('label', app.translator.trans(translationPrefix + 'field.type')),
                                Select.component({
                                    options: {
                                        301: app.translator.trans(translationPrefix + 'option.301'),
                                        302: app.translator.trans(translationPrefix + 'option.302'),
                                    },
                                    value: rule.type || DEFAULT_TYPE_WHEN_NOT_SET,
                                    onchange: value => {
                                        rule.type = parseInt(value);

                                        this.updateRules(rules);
                                    },
                                }),
                            ]),
                        ]),
                        m('.RedirectRule-row', [
                            m('.Form-group', Switch.component({
                                state: rule.enabled || DEFAULT_ENABLED_WHEN_NOT_SET,
                                onchange: value => {
                                    rule.enabled = value;

                                    this.updateRules(rules);
                                },
                                disabled: !rule.condition || !rule.redirect,
                                children: app.translator.trans(translationPrefix + 'field.enabled'),
                            })),
                            m('.Form-group', Button.component({
                                icon: 'trash',
                                className: 'Button Button--danger',
                                onclick: () => {
                                    rules.splice(index, 1);

                                    this.updateRules(rules);
                                },
                                children: app.translator.trans(translationPrefix + 'control.delete'),
                            })),
                        ]),
                    ]),
                ])
            )),
        ]);
    }

    updateRules(rules) {
        const toSave = rules.map(rule => {
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
}
