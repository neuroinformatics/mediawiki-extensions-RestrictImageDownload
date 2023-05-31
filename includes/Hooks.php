<?php

namespace MediaWiki\Extension\RestrictImageDownload;

use MediaWiki\MediaWikiServices;

class Hooks
{
    /**
     * Hook: ThumbnailBeforeProduceHTML.
     */
    public static function onThumbnailBeforeProduceHTML(\ThumbnailImage $thumbnail, array &$attribs, array &$linkAttribs)
    {
        global $wgUploadPath;
        global $wgRestrictImageFiles;
        $text = $thumbnail->getFile()->getTitle()->getText();
        if (!in_array($text, $wgRestrictImageFiles)) {
            return;
        }

        $attribs['oncontextmenu'] = 'return false;';
        if (!empty($linkAttribs['href']) && 0 === strpos($linkAttribs['href'], $wgUploadPath)) {
            $linkAttribs['href'] = 'javascript:void(0)';
        }
    }

    /**
     * Hook: LinkerMakeMediaLinkFile.
     */
    public static function onLinkerMakeMediaLinkFile(\Title $title, \File $file, string &$html, array &$attribs, string &$ret): bool
    {
        global $wgRestrictImageFiles;
        $text = $title->getText();
        if (!in_array($text, $wgRestrictImageFiles)) {
            return true;
        }
        // disable to link to original file.
        $ret = $html;

        return false;
    }

    /**
     * Hook: BeforePageDisplay.
     */
    public static function onBeforePageDisplay(\OutputPage &$out, \Skin &$skin)
    {
        global $wgUploadPath;
        global $wgRestrictImageFiles;
        $title = $out->getTitle();
        $text = $title->getText();
        if (!in_array($text, $wgRestrictImageFiles)) {
            return;
        }
        $file = MediaWikiServices::getInstance()->getRepoGroup()->findFile($title);
        if (!$file) {
            return;
        }
        $path = str_replace($wgUploadPath, '', $file->getUrl());
        $body = $out->getHTML();
        $body = preg_replace('/<a href="'.preg_quote($wgUploadPath.$path, '/').'"[^>]*>(.*)<\/a>/Us', '\1', $body);
        $body = preg_replace('/<a href="'.preg_quote($wgUploadPath.'/thumb'.$path.'/', '/').'[^"]*"[^>]*>(.*)<\/a>/Us', '\1', $body);
        $out->clearHTML();
        $out->addHTML($body);
    }
}
