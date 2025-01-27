<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;

class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate(['file' => 'required|file|max:2024']);

        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        $fileReceived = $receiver->receive(); // receive file

        if ($fileReceived->isFinished()) { // file uploading is complete / all chunks are uploaded
            $file = $fileReceived->getFile(); // get file
            $extension = $file->getClientOriginalExtension();
            $fileName = md5(time()) . '.' . $extension; // a unique file name

            $file_path = $file->storeAs('resumable-tmp', $fileName);

            unlink($file->getPathname());

            return response()->json([
                'name' => $fileName,
                'path' => $file_path,
            ]);
        }

        // otherwise return percentage information
        $handler = $fileReceived->handler();

        return response()->json([
            'done' => $handler->getPercentageDone(),
        ]);
    }
}
