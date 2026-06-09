<?php
return [
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_port' => (int) (getenv('DB_PORT') ?: 3306),
    'db_name' => getenv('DB_NAME') ?: 'db',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASSWORD') ?: '',
    'weather_api_key' => getenv('WEATHER_API_KEY') ?: '',
    'groq_api_key' => getenv('GROQ_API_KEY') ?: '',
    'groq_model' => getenv('GROQ_MODEL') ?: 'llama-3.1-8b-instant',
];
