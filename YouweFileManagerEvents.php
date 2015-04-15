<?php

namespace Youwe\FileManagerBundle;

/**
 * Class YouweFileManagerEvents
 * @package Youwe\FileManagerEvents
 */
final class YouweFileManagerEvents
{
    const AFTER_FILE_UPLOADED = 'after.file.uploaded';
    const AFTER_FILE_MOVED = 'after.file.moved';
    const AFTER_FILE_RENAMED = 'after.file.renamed';
    const AFTER_FILE_DELETED = 'after.file.deleted';
    const AFTER_FILE_PASTED = 'after.file.pasted';
    const AFTER_FILE_EXTRACTED = 'after.file.extracted';

    const BEFORE_FILE_UPLOADED = 'before.file.uploaded';
    const BEFORE_FILE_MOVED = 'before.file.moved';
    const BEFORE_FILE_RENAMED = 'before.file.renamed';
    const BEFORE_FILE_DELETED = 'before.file.deleted';
    const BEFORE_FILE_PASTED = 'before.file.pasted';
    const BEFORE_FILE_EXTRACTED = 'before.file.extracted';

    const BEFORE_FILE_DIR_CREATED = 'before.file.dir.created';
    const AFTER_FILE_DIR_CREATED = 'after.file.dir.created';
}

