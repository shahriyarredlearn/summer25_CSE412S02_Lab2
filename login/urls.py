from django.urls import path
from . import views

urlpatterns = [
    path('login', views.home, name='login_home'),
]
