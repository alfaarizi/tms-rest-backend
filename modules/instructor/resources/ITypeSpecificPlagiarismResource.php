<?php

namespace app\modules\instructor\resources;

interface ITypeSpecificPlagiarismResource {
    /**
     * URL where the plagiarism check results can be viewed, or `null`
     * if there are no results yet.
     * Usually points to `/instructor/plagiarism-result`, but may point
     * to external sites in case of online services.
     */
    function getUrl(): ?string;

    /**
     * File name of the main file (which should be returned by
     * `/instructor/plagiarism-result`), e.g. `index.html`
     */
    function getMainFileName(): string;
}
