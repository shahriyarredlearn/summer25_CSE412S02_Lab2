from django.urls import path
from . import views

urlpatterns = [
    path('about', views.home, name='About_home'),
]
