<?php
class synchro_ArticlefamilyScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return synchro_persistentdocument_articlefamily
     */
    protected function initPersistentDocument()
    {
    	return synchro_ArticlefamilyService::getInstance()->getNewDocumentInstance();
    }
}