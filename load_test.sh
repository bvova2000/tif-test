#!/bin/bash

echo "Начинаем нагрузочный тест..."
siege -c50 -t1M http://localhost:8080
echo "Тест завершен!"

