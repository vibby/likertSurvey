<?php
namespace Deployer;
require 'recipe/symfony3.php';

// Configuration

set('ssh_type', 'native');
set('ssh_multiplexing', true);

set('repository', 'https://github.com/vibby/likertSurvey.git');

set('bin/composer', 'composer');

add('shared_files', ['app/config/questions.yml']);
add('shared_dirs', []);

add('writable_dirs', []);

set('writable_mode', 'chmod');

// Servers

server('production', 'teep.fr')
    ->user('teepadmin')
    ->identityFile('~/.ssh/id_rsa.pub', '~/.ssh/id_rsa', '')
    ->set('deploy_path', '/var/www/vhosts/teep.fr/teep-research')
    ->pty(false);


server('productionAnonymous', 'teep.fr')
    ->user('teepadmin')
    ->identityFile('~/.ssh/id_rsa.pub', '~/.ssh/id_rsa', '')
    ->set('deploy_path', '/var/www/vhosts/teep.fr/teep-anonymous')
    ->pty(false);


// Tasks

/*
desc('Restart PHP-FPM service');
task('php-fpm:restart', function () {
    // The user must have rights for restart service
    // /etc/sudoers: username ALL=NOPASSWD:/bin/systemctl restart php-fpm.service
    run('sudo systemctl restart php-fpm.service');
});
after('deploy:symlink', 'php-fpm:restart');
*/

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.
//before('deploy:symlink', 'database:migrate');
