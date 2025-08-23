from django.urls import path
from . import views

urlpatterns = [
    path('files', views.home, name='files_home'),
]
