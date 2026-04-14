<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Phoenix token to users and unique constraints for likes/imported photos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD phoenix_api_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_likes_user_photo ON likes (user_id, photo_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_photos_user_image_url ON photos (user_id, image_url)');
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        $this->addSql('CREATE INDEX idx_photos_location_trgm ON photos USING gin (LOWER(COALESCE(location, \'\')) gin_trgm_ops)');
        $this->addSql('CREATE INDEX idx_photos_camera_trgm ON photos USING gin (LOWER(COALESCE(camera, \'\')) gin_trgm_ops)');
        $this->addSql('CREATE INDEX idx_photos_description_trgm ON photos USING gin (LOWER(COALESCE(description, \'\')) gin_trgm_ops)');
        $this->addSql('CREATE INDEX idx_users_username_trgm ON users USING gin (LOWER(username) gin_trgm_ops)');
        $this->addSql('CREATE INDEX idx_users_name_trgm ON users USING gin (LOWER(COALESCE(name, \'\')) gin_trgm_ops)');
        $this->addSql('CREATE INDEX idx_users_last_name_trgm ON users USING gin (LOWER(COALESCE(last_name, \'\')) gin_trgm_ops)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_users_last_name_trgm');
        $this->addSql('DROP INDEX idx_users_name_trgm');
        $this->addSql('DROP INDEX idx_users_username_trgm');
        $this->addSql('DROP INDEX idx_photos_description_trgm');
        $this->addSql('DROP INDEX idx_photos_camera_trgm');
        $this->addSql('DROP INDEX idx_photos_location_trgm');
        $this->addSql('DROP INDEX uniq_photos_user_image_url');
        $this->addSql('DROP INDEX uniq_likes_user_photo');
        $this->addSql('ALTER TABLE users DROP phoenix_api_token');
    }
}
