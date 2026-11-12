import {defineConfig} from '@playwright/test';
export default defineConfig({
 testDir:'./tests/browser', testMatch:'**/*.spec.js', fullyParallel:false, workers:1,
 use:{baseURL:'http://127.0.0.1:8000',trace:'retain-on-failure'},
 reporter:'list',
 webServer:{command:'php artisan serve --host=127.0.0.1 --port=8000',url:'http://127.0.0.1:8000/login',reuseExistingServer:false,timeout:30000},
});
