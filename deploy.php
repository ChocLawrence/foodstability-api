<?php
namespace Deployer;

require 'recipe/laravel.php';

// Project name
set('application', 'foodstability-api');

// Project repository
set('repository', 'git@github.com:ChocLawrence/foodstability-api.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 

//set('use_relative_symlinks', false);

// Shared files/dirs between deploys 
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server 
add('writable_dirs', []);


// Hosts

host('143.244.170.6')
    ->user('lawrence')
    ->identityFile('~/.ssh/foodstability_deployerkey')
    ->set('deploy_path', '/var/www/html/foodstability-api');    
    
// Tasks

task('build', function () {
    run('cd {{release_path}} && build');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');

