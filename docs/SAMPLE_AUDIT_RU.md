# Пример технического аудита - Modular WordPress Academy

## Executive summary

Архитектура разделена на три плагина с явными границами. Критичные данные доступа и прогресса хранятся в собственных индексированных таблицах. Защищенные REST endpoints проверяют аутентификацию/права, запись mapping в WooCommerce защищена capability + nonce, SQL с входными значениями параметризован.

Для production остаются важные задачи: автоматизированные integration tests, runtime-мониторинг, rate limiting для write endpoint, проверка backup/restore и формализованный release/rollback process.

## HIGH-01 - нет явного rate limiting для записи прогресса

**Область:** REST API / abuse prevention  
**Endpoint:** `POST /academy/v1/progress/complete`

Пользователь должен быть авторизован и иметь доступ к курсу. Unique key защищает от дублирования строки, однако скомпрометированная сессия все равно может создавать лишнюю нагрузку повторными запросами.

**Рекомендация:** application-level throttling либо rate limiting на уровне reverse proxy/WAF.

## HIGH-02 - нет автоматизированного WordPress integration test suite

Три плагина зависят от публичных контрактов между модулями. При росте кодовой базы ручной smoke-test недостаточен.

**Рекомендация:** добавить тесты enrollment, permission callbacks, lesson validation, idempotent progress update и доступ к API.

## MEDIUM-01 - audit log без retention policy

Таблица логов индексирована, но записи не архивируются и не удаляются.

**Рекомендация:** политика хранения 90-180 дней с WP-Cron/системным cron и архивированием при необходимости.

## MEDIUM-02 - guest order не создает ученика автоматически

Гостевой WooCommerce-заказ безопасно пропускается и логируется, но это может не соответствовать бизнес-процессу реального LMS.

**Рекомендация:** либо запретить guest checkout для курсов, либо реализовать явный account-provisioning flow.

## MEDIUM-03 - связь lesson -> course хранится в postmeta

Для небольшого LMS это рационально. При очень большом количестве уроков meta_query может стать узким местом.

**Рекомендация:** сначала профилирование на реальных данных; отдельная таблица только при подтвержденной проблеме.

## Что уже усилено после code review

- добавлена nonce-проверка при сохранении WooCommerce product -> course mapping;
- course ID проверяется как реальный `academy_course`;
- source reference очищается перед сохранением;
- progress endpoint проверяет тип и опубликованный статус урока;
- статический verification script фиксирует ключевые guardrails;
- Academy Courses и Academy Progress явно блокируют активацию без Academy Core.

## Приоритет remediation

1. Runtime integration test в реальном WordPress/WooCommerce.
2. Automated integration tests.
3. Centralized error monitoring.
4. Rate limiting.
5. Backup/restore verification.
6. Load test критичных API paths.
7. Log retention.
