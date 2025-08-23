from django.urls import path
from . import views

urlpatterns = [
    path('Register', views.home, name='Register_home'),  # example view
]
