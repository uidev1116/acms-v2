"use strict"

const { systemCmd } = require('./lib/system.js');

(async () => {
  try {
    await systemCmd('npm ci');
    await systemCmd('composer install');
    await systemCmd('cd src; composer install');
  } catch (err) {
    console.log(err);
  }
})();
