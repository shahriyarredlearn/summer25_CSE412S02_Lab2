from django.contrib import admin
from django.urls import path, include

urlpatterns = [
    path('admin/', admin.site.urls),
    path('About/', include('About.urls')),
    path('files/', include('files.urls')),
    path('login/', include('login.urls')),
    path('upload/', include('upload.urls')),  # fixed typo here
    path('register/', include('Register.urls')),
    
    # Optional: homepage route, if you want it to go to upload_home
    # path('', include('upload.urls')),  # you can uncomment if homepage is upload
]
