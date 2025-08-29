from django.urls import path
from . import views

urlpatterns = [
    path('', views.files_view, name='files_home'),
]
