<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GoogleDriveStreamController extends Controller
{
    /**
     * Stream a file from Google Drive using Service Account
     * 
     * @param string $fileId
     */
    public function stream($fileId, Request $request)
    {
        $credentialsPath = storage_path('app/google-drive/service-account.json');

        if (!file_exists($credentialsPath)) {
            return response()->json(['error' => 'Google Drive Service Account missing.'], 500);
        }

        try {
            $client = new \Google_Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope(\Google_Service_Drive::DRIVE_READONLY);

            $driveService = new \Google_Service_Drive($client);

            // Forward Range headers for seeking in video player
            $guzzleConfig = [];
            if ($request->header('Range')) {
                $guzzleConfig['headers'] = ['Range' => $request->header('Range')];
            }
            $guzzleClient = new \GuzzleHttp\Client($guzzleConfig);
            $client->setHttpClient($guzzleClient);

            // Get the file with 'alt' => 'media' to download the content
            $response = $driveService->files->get($fileId, ['alt' => 'media']);

            // Get the underlying PSR-7 Response object from Guzzle
            $httpResponse = $response->getBody();

            return response()->stream(function () use ($httpResponse) {
                while (!$httpResponse->eof()) {
                    echo $httpResponse->read(1024 * 8);
                    ob_flush();
                    flush();
                }
            }, $response->getStatusCode(), [
                'Content-Type' => $response->getHeaderLine('Content-Type') ?: 'video/mp4',
                'Content-Length' => $response->getHeaderLine('Content-Length'),
                'Accept-Ranges' => $response->getHeaderLine('Accept-Ranges'),
                'Content-Range' => $response->getHeaderLine('Content-Range'),
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Streaming failed: ' . $e->getMessage()], 500);
        }
    }
}
