package uk.me.rsw.blueline;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager.NameNotFoundException;
import android.net.Uri;
import android.os.Bundle;
import android.util.Log;
import android.view.KeyEvent;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Toast;
import android.widget.ViewSwitcher;

public class BluelineActivity extends Activity {
	private ViewSwitcher BluelineSwitcher;
	private WebView BluelineWebView;
	private boolean LoadingHidden = false;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(final Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.main);
        
        BluelineSwitcher = (ViewSwitcher) findViewById(R.id.viewSwitcher);
        BluelineWebView = (WebView) findViewById(R.id.webview);

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
        webSettings.setSupportZoom(true);
        webSettings.setSaveFormData(false);
        try {
			webSettings.setUserAgentString(getString(R.string.app_name)+" "+getPackageManager().getPackageInfo(getPackageName(), 0).versionName+" "+webSettings.getUserAgentString());
		} catch (NameNotFoundException e) {
			Log.e("tag", e.getMessage());
		}
        
        BluelineWebView.setWebViewClient(new BluelineWebViewClient());
        BluelineWebView.addJavascriptInterface(new JavaScriptInterface(this), "Android");
        BluelineWebView.setScrollBarStyle(WebView.SCROLLBARS_INSIDE_OVERLAY);
        
        if (savedInstanceState != null) {
        	BluelineWebView.restoreState(savedInstanceState);
        }
        else {
	        BluelineWebView.loadUrl("http://blueline.rsw.me.uk");
        }
    }
    
    /** Web Client */
    private class BluelineWebViewClient extends WebViewClient {
        @Override
        public boolean shouldOverrideUrlLoading(WebView view, String url) {
            if (Uri.parse(url).getHost().equals("blueline.rsw.me.uk")) {
                // Don't override local links
                return false;
            }
            Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
            startActivity(intent);
            return true;
        }
    }
    
    /** Javascript interface */
    private class JavaScriptInterface {
        Context mContext;
        JavaScriptInterface(Context c) {
            mContext = c;
        }
        
        /** Show a toast from the web page */
        public void showToast(String toast) {
            Toast.makeText(mContext, toast, Toast.LENGTH_SHORT).show();
        }
        
        /** Switch the view to the BluelineWebView */
        public void hideLoading() {
        	if( !LoadingHidden ) {
        		BluelineSwitcher.showNext();
        		LoadingHidden = true;
        	}
        }
    }
    
    /** Handle orientation changes */
    @Override
    protected void onSaveInstanceState(Bundle outState) {
    	BluelineWebView.saveState(outState);
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
