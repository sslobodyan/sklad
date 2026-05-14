php-sklad/
├── index.php, .htaccess
├── config/database.php
├── core/Controller.php, Model.php
├── controllers/
│   ├── WarehousesController.php
│   ├── MaterialsController.php
│   ├── MovementsController.php
│   ├── MovementsImportController.php
│   ├── MovementsExportController.php
│   ├── ResourcesController.php
│   ├── ResourceTypesController.php
│   ├── ResourceRatesController.php
│   ├── ResourceExportController.php
│   ├── ResourceReportController.php
│   ├── ResourceUsageExportController.php
│   ├── ReportsController.php
│   ├── SettingsController.php
│   ├── SimpleController.php
│   ├── SimpleDataHelper.php
│   └── traits/
│       ├── MovementRedirectTrait.php
│       ├── MovementValidationTrait.php
│       ├── ResourceValidationTrait.php
│       ├── ResourceContextTrait.php
│       ├── SimpleConfigTrait.php
│       └── SimpleResponseTrait.php
├── models/
│   ├── WarehouseModel.php
│   ├── MaterialModel.php
│   ├── MovementModel.php
│   ├── ConfigModel.php
│   ├── ResourceModel.php
│   ├── ResourceTypesModel.php
│   ├── ResourceRatesModel.php
│   ├── ResourceLogsModel.php
│   ├── ResourceReportModel.php
│   ├── ResourceUsageReportModel.php
│   └── traits/
│       ├── ResourceFormatTrait.php
│       └── ResourceChainTrait.php
├── helpers/
│   ├── XlsxParserHelper.php
│   └── XlsxGeneratorHelper.php
├── views/
│   ├── layout.php
│   ├── warehouses/, materials/, movements/, reports/, resources/, errors/
│   └── simple/
├── assets/
│   ├── css/style.css
│   └── js/
│       ├── main.js
│       ├── core/
│       ├── components/
│       └── modals/
└── sql/
    ├── schema.sql
    ├── migration_resources.sql
    └── migration_resources_v2.sql

