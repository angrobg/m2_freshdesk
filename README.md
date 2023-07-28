This extension need to searching, preparing and seending information about customer for your Freshdesk/Freshsales account. The extension is free, but you should install paid [Freshdesk](https://www.freshworks.com/apps/freshdesk/magento_2_connector/)/[Freshsales](https://www.freshworks.com/apps/freshworks_crm/magento_2_connector_1/) application

**How to install the extension:**
1) Download actual version of the extension
2) Unpack it on your Magento 2 store
3) Run following commands in console:
```
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
```
4) Go to the extension settings page (Stores > Configuration > MorfDev > Freshworks)
5) Generate API Token (click on "Generate new token" button or input own string and save configuration)

All configuration on Magento 2 store is done.
