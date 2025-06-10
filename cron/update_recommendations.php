<?php
// cron/update_recommendations.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/recommendation_functions.php';

calculateProductSimilarity();
error_log("Product recommendations updated at " . date('Y-m-d H:i:s'));