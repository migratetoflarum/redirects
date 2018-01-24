const gulp = require('flarum-gulp');

gulp({
    modules: {
        'migratetoflarum/redirects': 'src/**/*.js'
    }
});
