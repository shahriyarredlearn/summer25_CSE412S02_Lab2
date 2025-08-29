from django.contrib import admin
from django.urls import path, include
from . import views   # ✅ project-level views import করা হলো


urlpatterns = [
    path('admin/', admin.site.urls),
    path("", views.home_view, name="home"),   # ✅ Home page এখানে সেট করা হলো
    path('About/', include('About.urls')),
    #path('Register/', include('Register.urls')),
    path('login/', include('login.urls')),
    path('upload/', include('upload.urls')),
    path('files/', include('files.urls')),
    path('Register/', include('Register.urls')),

]
