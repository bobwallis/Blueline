package uk.me.rsw.blueline;

import android.app.Activity;
import android.os.Bundle;
import android.view.KeyEvent;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.widget.TextView;

public class BluelineActivity extends Activity {
	WebView BluelineWebView;
	TextView BluelineHeader;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.main);
        
        BluelineWebView = (WebView) findViewById(R.id.webview);
        BluelineHeader = (TextView) findViewById(R.id.header);
        
        WebSettings webSettings = BluelineWebView.getSettings();
        webSettings.setJavaScriptEnabled(true);
        webSettings.setAppCacheMaxSize(524288);
        webSettings.setAppCachePath("/data/data/uk.me.rsw.blueline/cache");
        webSettings.setAllowFileAccess(true);
        webSettings.setAppCacheEnabled(true);
        webSettings.setCacheMode(WebSettings.LOAD_DEFAULT);
        webSettings.setDatabasePath("/data/data/uk.me.rsw.blueline/cache");
        webSettings.setDatabaseEnabled(true);
        webSettings.setDomStorageEnabled(true);
        webSettings.setGeolocationEnabled(true);
        webSettings.setSupportZoom(true);
        webSettings.setSaveFormData(false);
        
        BluelineWebView.setScrollBarStyle(WebView.SCROLLBARS_OUTSIDE_OVERLAY);
        
        BluelineWebView.loadUrl("http://blueline.rsw.me.uk/?android");
    }
    
    /** Handle back button presses */
    @Override
    public boolean onKeyDown(int keyCode, KeyEvent event) {
        if ((keyCode == KeyEvent.KEYCODE_BACK) && BluelineWebView.canGoBack()) {
        	BluelineWebView.goBack();
            return true;
        }
        return super.onKeyDown(keyCode, event);
    }
}