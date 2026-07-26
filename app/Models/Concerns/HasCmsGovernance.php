<?php

namespace App\Models\Concerns;

use App\Models\CmsTranslation;
use App\Models\ContentVersion;
use App\Models\ContentWorkflow;

trait HasCmsGovernance
{
    public function workflow()
    {
        return $this->morphOne(ContentWorkflow::class, 'workflowable');
    }

    public function versions()
    {
        return $this->morphMany(ContentVersion::class, 'versionable')->latest('version_number');
    }

    public function cmsTranslations()
    {
        return $this->morphMany(CmsTranslation::class, 'translatable');
    }
}
