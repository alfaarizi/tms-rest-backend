<?php

namespace app\models;

/**
 * An active record that has a corresponding file on disk.
 *
 * @property-read string $path The absolute path of the file on disk
 */
abstract class File extends \yii\db\ActiveRecord
{
    /**
     * If `true`, deletion will succeed even if the physical
     * file could not be deleted, useful if you know you’re
     * in an inconsistent state.
     */
    public bool $forceDelete = false;

    /**
     * @return string Absolute path.
     */
    abstract public function getPath(): string;

    /** {@inheritdoc} */
    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        return @unlink($this->path) || $this->forceDelete;
    }
}
