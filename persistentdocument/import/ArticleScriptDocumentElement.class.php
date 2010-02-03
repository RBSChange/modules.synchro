<?php
class synchro_ArticleScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return synchro_persistentdocument_article
     */
    protected function initPersistentDocument()
    {
    	return synchro_ArticleService::getInstance()->getNewDocumentInstance();
    }
}