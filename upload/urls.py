from django.urls import path
from . import views

urlpatterns = [
    path('up', views.upload, name='upload_home'),
]
