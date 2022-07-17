Настройки для подключения Yandex Object Storage
```yaml
enjoyscms/catalog:
    productImageStorage: s3_yandex
    storageUploads:
        s3_yandex:
            \EnjoysCMS\Module\Catalog\StorageUpload\S3:
                bucket: enjoys
                prefix: catalog # можно не указывать
                clientOptions:
                    endpoint: https://storage.yandexcloud.net
                    credentials:
                        key: ---KEY---
                        secret: ---SECRET---
                    region: ru
                    version: latest
```
