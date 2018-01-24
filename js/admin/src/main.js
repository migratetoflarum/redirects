import {extend} from 'flarum/extend';
import app from 'flarum/app';
import RedirectsSettingsModal from 'migratetoflarum/redirects/components/RedirectsSettingsModal';

app.initializers.add('migratetoflarum-redirects', app => {
    app.extensionSettings['migratetoflarum-redirects'] = () => app.modal.show(new RedirectsSettingsModal());
});
